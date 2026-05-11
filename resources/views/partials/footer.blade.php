<footer class="ppmf-footer">
  <div>
    &copy; {{ date('Y') }} <strong>Punjab Performance Management Framework</strong>
    · Government of Punjab, Pakistan
    · All rights reserved.
  </div>
  <div style="display:flex;align-items:center;gap:16px;">
    <span><i class="bi bi-clock" style="font-size:12px;"></i> Last sync: {{ now()->format('d M Y, h:i A') }}</span>
    <span>·</span>
    <a href="#">Privacy Policy</a>
    <span>·</span>
    <a href="#">Help & Support</a>
    <span>·</span>
    <span style="background:var(--gov-green-light);color:var(--gov-green);padding:2px 10px;border-radius:20px;font-weight:600;font-size:10.5px;">
      v2.0.0
    </span>
  </div>
</footer>
