<style>
.ms-wrap { padding: 1rem 1.25rem 2rem; font-family: 'Segoe UI', system-ui, sans-serif; font-size:1.4rem; color:#0f172a; }
.ms-wrap *, .ms-wrap *::before, .ms-wrap *::after { box-sizing: border-box; }
.ms-head { display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:.75rem; margin-bottom:1rem; }
.ms-head h1 { font-size:2rem; font-weight:800; margin:.25rem 0 0; }
.ms-wrap .tr { text-align:right; }
.ms-head p { color:#475569; margin:.2rem 0 0; }
.ms-back { color:#0f766e; font-weight:700; text-decoration:none; }
.ms-alert { padding:.65rem 1rem; border-radius:.5rem; margin-bottom:.875rem; font-weight:500; }
.ms-ok  { background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; }
.ms-err { background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; }
.ms-info { background:#f0fdfa; border:1px solid #99f6e4; color:#115e59; border-radius:.6rem; padding:.75rem 1rem; margin-bottom:1rem; line-height:1.6; }
.ms-warn { background:#fffbeb; border:1px solid #fde68a; color:#92400e; border-radius:.6rem; padding:.75rem 1rem; margin-bottom:1rem; line-height:1.6; }
.ms-card { background:#fff; border:1px solid #e2e8f0; border-radius:.6rem; margin-bottom:1rem; overflow:hidden; }
.ms-card-hd { padding:.65rem 1rem; border-bottom:1px solid #e2e8f0; font-weight:700; display:flex; justify-content:space-between; align-items:center; gap:.5rem; flex-wrap:wrap; }
.ms-card-bd { padding:1rem; }
.ms-tbl-wrap { overflow-x:auto; }
.ms-tbl { width:100%; border-collapse:collapse; min-width:720px; }
.ms-tbl th { background:#f8fafc; text-align:left; padding:.55rem .75rem; border-bottom:2px solid #e2e8f0; color:#475569; white-space:nowrap; }
.ms-tbl td { padding:.6rem .75rem; border-bottom:1px solid #f1f5f9; vertical-align:top; }
.ms-tbl tr:last-child td { border-bottom:none; }
.ms-form { display:flex; flex-wrap:wrap; gap:.75rem; align-items:flex-end; }
.ms-fg { display:flex; flex-direction:column; gap:.25rem; }
.ms-fg label { font-weight:700; color:#475569; font-size:1.3rem; }
.ms-fg input[type=text], .ms-fg input[type=date], .ms-fg input[type=number], .ms-fg textarea, .ms-fg select {
    padding:.4rem .6rem; border:1px solid #e2e8f0; border-radius:.4rem; font-size:1.4rem; background:#f8fafc; color:#0f172a; }
.ms-fg textarea { min-width:280px; min-height:60px; font-family:inherit; }
.ms-chk { display:flex; gap:.4rem; align-items:center; font-weight:600; }
.ms-btn { padding:.45rem 1rem; border:none; border-radius:.4rem; font-size:1.4rem; font-weight:700; cursor:pointer; background:#0f766e; color:#fff; text-decoration:none; display:inline-block; }
.ms-btn:hover { background:#115e59; color:#fff; text-decoration:none; }
.ms-btn-sm { padding:.2rem .6rem; font-size:1.3rem; }
.ms-btn-ghost { background:#fff; color:#0f766e; border:1px solid #99f6e4; }
.ms-btn-ghost:hover { background:#f0fdfa; color:#0f766e; }
.ms-btn-danger { background:#fff; color:#dc2626; border:1px solid #fecaca; }
.ms-btn-danger:hover { background:#dc2626; color:#fff; }
.ms-badge { display:inline-block; padding:.12rem .55rem; border-radius:9999px; font-size:1.25rem; font-weight:700; white-space:nowrap; }
.ms-b-musim { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
.ms-b-persiapan { background:#ccfbf1; color:#115e59; border:1px solid #5eead4; }
.ms-b-luar { background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; }
.ms-b-tanpa_tanggal { background:#fffbeb; color:#92400e; border:1px solid #fde68a; }
.ms-muted { color:#94a3b8; font-size:1.3rem; }
.ms-period { display:flex; gap:.5rem; align-items:center; margin-bottom:.3rem; flex-wrap:wrap; }
.ms-types { max-height:460px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:.5rem; }
.ms-type-row { display:flex; gap:.6rem; align-items:center; padding:.45rem .75rem; border-bottom:1px solid #f1f5f9; cursor:pointer; }
.ms-type-row:hover { background:#f0fdfa; }
.ms-type-row.is-mine { background:#f0fdfa; }
.ms-type-row input { width:18px; height:18px; flex-shrink:0; }
.ms-type-name { font-weight:600; }
.ms-tag-musiman { font-size:1.15rem; background:#fef3c7; color:#92400e; border-radius:9999px; padding:.05rem .45rem; margin-left:.35rem; }
.ms-tag-other { font-size:1.15rem; background:#f1f5f9; color:#475569; border-radius:9999px; padding:.05rem .45rem; margin-left:.35rem; }
.ms-search { width:100%; padding:.5rem .75rem; border:1px solid #e2e8f0; border-radius:.45rem; font-size:1.4rem; margin-bottom:.6rem; }
.ms-sticky-save { position:sticky; bottom:0; background:#fff; border-top:1px solid #e2e8f0; padding:.75rem 1rem; display:flex; gap:.75rem; align-items:center; z-index:3; }
@media(max-width:768px) { .ms-form { flex-direction:column; align-items:stretch; } .ms-fg textarea { min-width:0; } }
</style>
