package com.ntnmart.kasirhp;

import android.os.Bundle;

import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {
    @Override
    public void onCreate(Bundle savedInstanceState) {
        // Plugin lokal (printer Bluetooth + penyimpanan token) harus didaftarkan sebelum super.onCreate.
        registerPlugin(KasirNativePlugin.class);
        super.onCreate(savedInstanceState);
    }
}
