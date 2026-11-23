package com.ntnmart.kasirhp;

import android.Manifest;
import android.annotation.SuppressLint;
import android.bluetooth.BluetoothAdapter;
import android.bluetooth.BluetoothClass;
import android.bluetooth.BluetoothDevice;
import android.bluetooth.BluetoothManager;
import android.bluetooth.BluetoothSocket;
import android.content.Context;
import android.content.SharedPreferences;
import android.os.Build;
import android.security.keystore.KeyGenParameterSpec;
import android.security.keystore.KeyProperties;
import android.util.Base64;
import android.util.Log;

import com.getcapacitor.JSArray;
import com.getcapacitor.JSObject;
import com.getcapacitor.PermissionState;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;
import com.getcapacitor.annotation.Permission;
import com.getcapacitor.annotation.PermissionCallback;

import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.lang.reflect.Method;
import java.nio.charset.StandardCharsets;
import java.security.KeyStore;
import java.util.Set;
import java.util.UUID;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;

import javax.crypto.Cipher;
import javax.crypto.KeyGenerator;
import javax.crypto.SecretKey;
import javax.crypto.spec.GCMParameterSpec;

/**
 * Kode native Kasir HP:
 * 1. Penyimpanan rahasia (token perangkat) terenkripsi dengan kunci Android Keystore.
 * 2. Printer thermal Bluetooth Classic (SPP / RFCOMM) untuk mencetak ESC/POS.
 */
@CapacitorPlugin(
    name = "KasirNative",
    permissions = { @Permission(alias = "bluetooth", strings = { Manifest.permission.BLUETOOTH_CONNECT }) }
)
public class KasirNativePlugin extends Plugin {

    private static final String TAG = "KasirNative";
    private static final UUID SPP_UUID = UUID.fromString("00001101-0000-1000-8000-00805F9B34FB");
    private static final String KEY_ALIAS = "kasirhp_secret_v1";
    private static final String PREFS = "kasirhp_secure";
    private static final long CONNECT_ATTEMPT_MS = 8000;
    private static final long CONNECT_TOTAL_MS = 15000;

    private final ExecutorService io = Executors.newSingleThreadExecutor();
    private final Object lock = new Object();
    private BluetoothSocket socket;
    private OutputStream out;
    private String address;
    private String name;

    // ------------------------------------------------------------------
    // Penyimpanan rahasia
    // ------------------------------------------------------------------

    private SharedPreferences prefs() {
        return getContext().getSharedPreferences(PREFS, Context.MODE_PRIVATE);
    }

    private SecretKey secretKey() throws Exception {
        KeyStore ks = KeyStore.getInstance("AndroidKeyStore");
        ks.load(null);
        if (ks.containsAlias(KEY_ALIAS)) {
            return ((KeyStore.SecretKeyEntry) ks.getEntry(KEY_ALIAS, null)).getSecretKey();
        }
        KeyGenerator kg = KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES, "AndroidKeyStore");
        kg.init(new KeyGenParameterSpec.Builder(KEY_ALIAS, KeyProperties.PURPOSE_ENCRYPT | KeyProperties.PURPOSE_DECRYPT)
            .setBlockModes(KeyProperties.BLOCK_MODE_GCM)
            .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
            .setKeySize(256)
            .build());
        return kg.generateKey();
    }

    private String encrypt(String value) {
        try {
            Cipher c = Cipher.getInstance("AES/GCM/NoPadding");
            c.init(Cipher.ENCRYPT_MODE, secretKey());
            byte[] ct = c.doFinal(value.getBytes(StandardCharsets.UTF_8));
            return "v1:" + Base64.encodeToString(c.getIV(), Base64.NO_WRAP) + ":" + Base64.encodeToString(ct, Base64.NO_WRAP);
        } catch (Exception e) {
            // Keystore rusak di sebagian HP murah: tetap simpan (prefs hanya bisa dibaca aplikasi ini).
            Log.w(TAG, "Keystore gagal, simpan tanpa enkripsi", e);
            return "plain:" + value;
        }
    }

    private String decrypt(String stored) {
        if (stored == null) return null;
        if (stored.startsWith("plain:")) return stored.substring(6);
        try {
            String[] p = stored.split(":");
            byte[] iv = Base64.decode(p[1], Base64.NO_WRAP);
            byte[] ct = Base64.decode(p[2], Base64.NO_WRAP);
            Cipher c = Cipher.getInstance("AES/GCM/NoPadding");
            c.init(Cipher.DECRYPT_MODE, secretKey(), new GCMParameterSpec(128, iv));
            return new String(c.doFinal(ct), StandardCharsets.UTF_8);
        } catch (Exception e) {
            Log.w(TAG, "Gagal membuka rahasia", e);
            return null;
        }
    }

    @PluginMethod
    public void getSecret(PluginCall call) {
        String key = call.getString("key");
        JSObject r = new JSObject();
        r.put("value", decrypt(prefs().getString("s_" + key, null)));
        call.resolve(r);
    }

    @PluginMethod
    public void setSecret(PluginCall call) {
        String key = call.getString("key");
        String value = call.getString("value");
        if (key == null || value == null) {
            call.reject("key/value kosong");
            return;
        }
        prefs().edit().putString("s_" + key, encrypt(value)).commit();
        call.resolve();
    }

    @PluginMethod
    public void removeSecret(PluginCall call) {
        prefs().edit().remove("s_" + call.getString("key")).commit();
        call.resolve();
    }

    // ------------------------------------------------------------------
    // Printer Bluetooth
    // ------------------------------------------------------------------

    private boolean needsBtPermission() {
        return Build.VERSION.SDK_INT >= 31 && getPermissionState("bluetooth") != PermissionState.GRANTED;
    }

    private BluetoothAdapter adapter() {
        BluetoothManager bm = (BluetoothManager) getContext().getSystemService(Context.BLUETOOTH_SERVICE);
        return bm != null ? bm.getAdapter() : null;
    }

    /** null = siap; selain itu pesan kesalahan untuk kasir. */
    private String btProblem() {
        BluetoothAdapter a = adapter();
        if (a == null) return "HP ini tidak punya Bluetooth.";
        if (!a.isEnabled()) return "Bluetooth HP mati. Nyalakan Bluetooth dulu.";
        return null;
    }

    private JSObject state() {
        JSObject s = new JSObject();
        synchronized (lock) {
            s.put("connected", socket != null && socket.isConnected());
            s.put("address", address);
            s.put("name", name);
        }
        return s;
    }

    private void emitState() {
        notifyListeners("printerState", state(), true);
    }

    @PluginMethod
    public void btListPaired(PluginCall call) {
        if (needsBtPermission()) {
            requestPermissionForAlias("bluetooth", call, "btListPairedPerm");
            return;
        }
        doListPaired(call);
    }

    @PermissionCallback
    private void btListPairedPerm(PluginCall call) {
        if (getPermissionState("bluetooth") != PermissionState.GRANTED) {
            call.reject("Izin Bluetooth ditolak. Buka Pengaturan Android > Aplikasi > Kasir HP > Izin, lalu izinkan Perangkat sekitar.");
            return;
        }
        doListPaired(call);
    }

    @SuppressLint("MissingPermission")
    private void doListPaired(PluginCall call) {
        String problem = btProblem();
        if (problem != null) {
            call.reject(problem);
            return;
        }
        JSArray list = new JSArray();
        Set<BluetoothDevice> bonded = adapter().getBondedDevices();
        if (bonded != null) {
            for (BluetoothDevice d : bonded) {
                JSObject o = new JSObject();
                o.put("name", d.getName() != null ? d.getName() : d.getAddress());
                o.put("address", d.getAddress());
                BluetoothClass bc = d.getBluetoothClass();
                o.put("printer", bc != null && bc.getMajorDeviceClass() == BluetoothClass.Device.Major.IMAGING);
                list.put(o);
            }
        }
        JSObject r = new JSObject();
        r.put("devices", list);
        call.resolve(r);
    }

    @PluginMethod
    public void btConnect(PluginCall call) {
        if (needsBtPermission()) {
            requestPermissionForAlias("bluetooth", call, "btConnectPerm");
            return;
        }
        doConnect(call);
    }

    @PermissionCallback
    private void btConnectPerm(PluginCall call) {
        if (getPermissionState("bluetooth") != PermissionState.GRANTED) {
            call.reject("Izin Bluetooth ditolak. Buka Pengaturan Android > Aplikasi > Kasir HP > Izin, lalu izinkan Perangkat sekitar.");
            return;
        }
        doConnect(call);
    }

    private void doConnect(PluginCall call) {
        String addr = call.getString("address");
        if (addr == null || addr.isEmpty()) {
            call.reject("Printer belum dipilih.");
            return;
        }
        io.execute(() -> {
            try {
                connectInternal(addr);
                call.resolve(state());
            } catch (Exception e) {
                call.reject(e.getMessage() != null ? e.getMessage() : "Gagal menyambung ke printer.");
            }
        });
    }

    /** Jalan di thread io. Melempar IOException dengan pesan yang jelas. */
    @SuppressLint("MissingPermission")
    private void connectInternal(String addr) throws IOException {
        synchronized (lock) {
            if (socket != null && socket.isConnected() && addr.equalsIgnoreCase(address)) return;
        }
        closeInternal(false);

        String problem = btProblem();
        if (problem != null) throw new IOException(problem);

        BluetoothAdapter a = adapter();
        BluetoothDevice device;
        try {
            device = a.getRemoteDevice(addr);
        } catch (IllegalArgumentException e) {
            throw new IOException("Alamat printer tidak valid.");
        }
        try {
            a.cancelDiscovery();
        } catch (Exception ignored) {
        }

        BluetoothSocket s = null;
        IOException last = null;
        // Printer mati/jauh: jangan sampai kasir menunggu lama. Batas total ±15 detik.
        long deadline = System.currentTimeMillis() + CONNECT_TOTAL_MS;
        for (int attempt = 0; attempt < 3 && s == null; attempt++) {
            long left = deadline - System.currentTimeMillis();
            if (left < 1500) break;
            BluetoothSocket candidate = null;
            try {
                if (attempt == 0) {
                    candidate = device.createRfcommSocketToServiceRecord(SPP_UUID);
                } else if (attempt == 1) {
                    candidate = device.createInsecureRfcommSocketToServiceRecord(SPP_UUID);
                } else {
                    // Banyak printer murah hanya mau lewat kanal 1.
                    Method m = device.getClass().getMethod("createRfcommSocket", int.class);
                    candidate = (BluetoothSocket) m.invoke(device, 1);
                }
                connectWithTimeout(candidate, Math.min(CONNECT_ATTEMPT_MS, left));
                s = candidate;
            } catch (Exception e) {
                last = e instanceof IOException ? (IOException) e : new IOException(e.getMessage());
                try {
                    if (candidate != null) candidate.close();
                } catch (IOException ignored) {
                }
            }
        }
        if (s == null) {
            Log.w(TAG, "Gagal sambung printer", last);
            throw new IOException("Printer tidak tersambung. Pastikan printer menyala, dekat dengan HP, dan tidak sedang dipakai HP lain.");
        }

        synchronized (lock) {
            socket = s;
            out = s.getOutputStream();
            address = addr;
            String n = device.getName();
            name = n != null ? n : addr;
        }
        startReader(s);
        emitState();
    }

    /** BluetoothSocket.connect() tidak punya batas waktu; tutup socket bila terlalu lama. */
    private void connectWithTimeout(BluetoothSocket socket, long timeoutMs) throws IOException {
        final IOException[] error = new IOException[1];
        Thread t = new Thread(() -> {
            try {
                socket.connect();
            } catch (IOException e) {
                error[0] = e;
            }
        }, "printer-connect");
        t.start();
        try {
            t.join(timeoutMs);
        } catch (InterruptedException ignored) {
        }
        if (t.isAlive()) {
            try {
                socket.close(); // membatalkan connect() yang masih menunggu
            } catch (IOException ignored) {
            }
            throw new IOException("Waktu menyambung ke printer habis.");
        }
        if (error[0] != null) throw error[0];
        if (!socket.isConnected()) throw new IOException("Printer tidak tersambung.");
    }

    /** Membaca dari printer supaya tahu kapan sambungan putus (printer dimatikan/menjauh). */
    private void startReader(BluetoothSocket s) {
        Thread t = new Thread(() -> {
            byte[] buf = new byte[64];
            try {
                InputStream in = s.getInputStream();
                while (true) {
                    if (in.read(buf) < 0) break;
                }
            } catch (IOException ignored) {
            }
            boolean current;
            synchronized (lock) {
                current = socket == s;
            }
            if (current) {
                closeInternal(true);
            }
        }, "printer-reader");
        t.setDaemon(true);
        t.start();
    }

    private void closeInternal(boolean notify) {
        BluetoothSocket s;
        synchronized (lock) {
            s = socket;
            socket = null;
            out = null;
        }
        if (s != null) {
            try {
                s.close();
            } catch (IOException ignored) {
            }
            if (notify) emitState();
        }
    }

    @PluginMethod
    public void btDisconnect(PluginCall call) {
        io.execute(() -> {
            closeInternal(false);
            synchronized (lock) {
                address = null;
                name = null;
            }
            emitState();
            call.resolve();
        });
    }

    @PluginMethod
    public void btStatus(PluginCall call) {
        call.resolve(state());
    }

    @PluginMethod
    public void btWrite(PluginCall call) {
        if (needsBtPermission()) {
            requestPermissionForAlias("bluetooth", call, "btWritePerm");
            return;
        }
        doWrite(call);
    }

    @PermissionCallback
    private void btWritePerm(PluginCall call) {
        if (getPermissionState("bluetooth") != PermissionState.GRANTED) {
            call.reject("Izin Bluetooth ditolak.");
            return;
        }
        doWrite(call);
    }

    private void doWrite(PluginCall call) {
        String data = call.getString("data");
        String addr = call.getString("address");
        if (data == null) {
            call.reject("Tidak ada data cetak.");
            return;
        }
        final byte[] bytes = Base64.decode(data, Base64.DEFAULT);
        io.execute(() -> {
            String target = addr;
            synchronized (lock) {
                if (target == null || target.isEmpty()) target = address;
            }
            if (target == null) {
                call.reject("Printer belum dipilih. Pilih printer di Pengaturan.");
                return;
            }
            boolean wasConnected;
            synchronized (lock) {
                wasConnected = socket != null && socket.isConnected() && target.equalsIgnoreCase(address);
            }
            // Sambung ulang satu kali HANYA bila sambungan lama ternyata sudah putus saat menulis.
            // Kalau printer memang tidak terjangkau, langsung beri tahu kasir (tidak menunggu dua kali).
            for (int attempt = 0; attempt < (wasConnected ? 2 : 1); attempt++) {
                try {
                    connectInternal(target);
                    writeChunks(bytes);
                    call.resolve(state());
                    return;
                } catch (IOException e) {
                    closeInternal(true);
                    if (attempt == (wasConnected ? 1 : 0)) {
                        call.reject(friendlyMessage(e));
                        return;
                    }
                }
            }
        });
    }

    private static String friendlyMessage(IOException e) {
        String msg = e.getMessage();
        boolean friendly = msg != null && (msg.startsWith("Printer") || msg.startsWith("Bluetooth") || msg.startsWith("HP ") || msg.startsWith("Alamat"));
        return friendly ? msg : "Gagal mencetak: sambungan ke printer putus. Coba lagi.";
    }

    private void writeChunks(byte[] bytes) throws IOException {
        OutputStream o;
        synchronized (lock) {
            o = out;
        }
        if (o == null) throw new IOException("Printer tidak tersambung.");
        final int chunk = 256;
        for (int i = 0; i < bytes.length; i += chunk) {
            o.write(bytes, i, Math.min(chunk, bytes.length - i));
            o.flush();
            // Printer murah punya buffer kecil; beri jeda supaya tidak ada huruf yang hilang.
            try {
                Thread.sleep(12);
            } catch (InterruptedException ignored) {
            }
        }
    }

    @Override
    protected void handleOnDestroy() {
        closeInternal(false);
        io.shutdown();
        super.handleOnDestroy();
    }
}
