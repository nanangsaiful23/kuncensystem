<style>
.sdk { padding: 14px 14px 40px; max-width: 760px; margin: 0 auto; font-size: 16px;
       --ink:#0f172a; --ink2:#475569; --ink3:#94a3b8; --bdr:#e2e8f0; --card:#fff;
       --bad:#dc2626; --bad-bg:#fef2f2; --ok:#16a34a; --ok-bg:#f0fdf4;
       --warn:#d97706; --warn-bg:#fffbeb; --blue:#2563eb; --blue-bg:#eff6ff; }
.sdk, .sdk * { box-sizing: border-box; }
.sdk h1 { font-size: 22px; font-weight: 700; margin: 0 0 4px; color: var(--ink); }
.sdk .sdk-sub { color: var(--ink2); font-size: 14px; margin: 0 0 14px; }
.sdk-card { background: var(--card); border: 1px solid var(--bdr); border-radius: 12px;
            padding: 14px; margin-bottom: 12px; }
.sdk-card h2 { font-size: 16px; font-weight: 600; margin: 0 0 10px; color: var(--ink); }
.sdk-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px;
           min-height: 48px; padding: 10px 16px; border-radius: 10px; border: 1px solid var(--bdr);
           background: #fff; color: var(--ink) !important; font-size: 16px; font-weight: 600;
           cursor: pointer; text-decoration: none !important; width: 100%; }
.sdk-btn i { color: inherit !important; }
.sdk-btn:disabled { opacity: .55; }
.sdk-btn-prim { background: var(--blue); border-color: var(--blue); color: #fff !important; }
.sdk-btn-ok   { background: var(--ok); border-color: var(--ok); color: #fff !important; }
.sdk-btn-bad  { background: #fff; border-color: var(--bad); color: var(--bad) !important; }
.sdk-btn-sm   { min-height: 38px; width: auto; padding: 6px 12px; font-size: 14px; }
.sdk-grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.sdk-input { width: 100%; min-height: 48px; padding: 10px 12px; border: 1px solid var(--bdr);
             border-radius: 10px; font-size: 16px; color: var(--ink); background: #fff; }
.sdk-input:focus { outline: none; border-color: var(--blue); }
.sdk-qty { font-size: 28px; font-weight: 700; text-align: center; min-height: 64px; }
.sdk-label { display: block; font-size: 12px; font-weight: 700; letter-spacing: .06em;
             text-transform: uppercase; color: var(--ink3); margin: 10px 0 4px; }
.sdk-row { display: flex; gap: 8px; align-items: stretch; }
.sdk-row > .sdk-input { flex: 1; min-width: 0; }
.sdk-row > .sdk-btn { width: auto; flex: none; }
.sdk-msg { border-radius: 10px; padding: 10px 12px; font-size: 15px; margin-bottom: 12px; display: none; }
.sdk-msg.bad  { display: block; background: var(--bad-bg);  color: var(--bad); }
.sdk-msg.info { display: block; background: var(--blue-bg); color: #1e3a8a; }
.sdk-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-bottom: 12px; }
.sdk-stat { background: var(--card); border: 1px solid var(--bdr); border-radius: 10px;
            padding: 8px 6px; text-align: center; }
.sdk-stat b { display: block; font-size: 22px; font-weight: 700; color: var(--ink); }
.sdk-stat small { display: block; font-size: 11px; color: var(--ink2); }
.sdk-stat.ok b { color: var(--ok); } .sdk-stat.bad b { color: var(--bad); } .sdk-stat.warn b { color: var(--warn); }
.sdk-name { font-size: 19px; font-weight: 700; color: var(--ink); line-height: 1.3; }
.sdk-meta { font-size: 14px; color: var(--ink2); margin-top: 2px; }
.sdk-item { display: flex; gap: 10px; align-items: center; padding: 10px 0; border-top: 1px solid var(--bdr); }
.sdk-item:first-child { border-top: 0; }
.sdk-item-main { flex: 1; min-width: 0; }
.sdk-item-name { font-weight: 600; color: var(--ink); word-break: break-word; }
.sdk-item-sub { font-size: 13px; color: var(--ink2); }
.sdk-pill { flex: none; font-weight: 700; font-size: 14px; padding: 4px 10px; border-radius: 999px; white-space: nowrap; }
.sdk-pill.ok   { background: var(--ok-bg);   color: var(--ok); }
.sdk-pill.bad  { background: var(--bad-bg);  color: var(--bad); }
.sdk-pill.warn { background: var(--warn-bg); color: var(--warn); }
.sdk-del { flex: none; border: 0; background: transparent; padding: 8px; color: var(--ink3); font-size: 18px; }
.sdk-del i { color: var(--ink3) !important; }
.sdk-result { text-align: center; border-radius: 12px; padding: 14px; margin-bottom: 12px; display: none; }
.sdk-result.ok { display: block; background: var(--ok-bg); color: var(--ok); }
.sdk-result.bad { display: block; background: var(--bad-bg); color: var(--bad); }
.sdk-result.warn { display: block; background: var(--warn-bg); color: var(--warn); }
.sdk-result .big { font-size: 24px; font-weight: 700; }
.sdk-result .det { font-size: 14px; color: var(--ink2); margin-top: 4px; }
.sdk-system { margin-top: 12px; padding: 10px 12px; border-radius: 10px; background: var(--blue-bg); text-align: center; }
.sdk-system-num { font-size: 28px; font-weight: 700; color: #1e3a8a; line-height: 1.2; }
.sdk-system-num small { font-size: 15px; font-weight: 600; color: #1e3a8a; }
.sdk-live { min-height: 22px; margin-top: 6px; text-align: center; font-size: 15px; font-weight: 600; }
.sdk-live.ok { color: var(--ok); } .sdk-live.bad { color: var(--bad); } .sdk-live.warn { color: var(--warn); }
#sdk-reader { width: 100%; border-radius: 10px; overflow: hidden; background: #000; }
#sdk-reader video { width: 100% !important; display: block; }
.sdk-suggest { border: 1px solid var(--bdr); border-radius: 10px; margin-top: 6px; overflow: hidden; display: none; }
.sdk-suggest button { display: block; width: 100%; text-align: left; padding: 12px; border: 0;
                      border-top: 1px solid var(--bdr); background: #fff; font-size: 15px; color: var(--ink); }
.sdk-suggest button:first-child { border-top: 0; }
.sdk-suggest small { color: var(--ink2); display: block; }
.sdk-empty { color: var(--ink2); text-align: center; padding: 14px 0; font-size: 15px; }
@media (max-width: 360px) { .sdk-stats { grid-template-columns: repeat(2, 1fr); } }
</style>
