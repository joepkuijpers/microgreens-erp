<?php
/**
 * registration_timer.php — gedeelde meetarchitectuur (3 variabelen)
 * Inladen in header.php vóór </head>. Gratis voor alle B-modules.
 *
 * Registratietijd (server) + Active Interaction + Screen Availability (JS).
 * Screen Availability wordt NOOIT als werktijd getoeld.
 */
?>
<script>
(function(){
  var t0 = performance.now();
  var t1 = null;
  function mark(){ if(t1 === null){ t1 = performance.now(); } }
  function inject(f){
    if(f.dataset.rtBound) return;
    f.dataset.rtBound = "1";
    ["focusin","input","click"].forEach(function(ev){
      f.addEventListener(ev, mark, true);
    });
    f.addEventListener("submit", function(){
      if(!f.elements["active_interaction_ms"]){
        var a = document.createElement("input");
        a.type = "hidden"; a.name = "active_interaction_ms"; a.value = "";
        f.appendChild(a);
      }
      if(!f.elements["screen_availability_ms"]){
        var s = document.createElement("input");
        s.type = "hidden"; s.name = "screen_availability_ms"; s.value = "";
        f.appendChild(s);
      }
      var t2 = performance.now();
      f.elements["active_interaction_ms"].value = (t1 === null) ? 0 : Math.round(t2 - t1);
      f.elements["screen_availability_ms"].value = Math.round(t2 - t0);
    });
  }
  function bindAll(){ document.querySelectorAll("form").forEach(inject); }
  if(document.readyState === "loading"){
    document.addEventListener("DOMContentLoaded", bindAll);
  } else { bindAll(); }
})();
</script>
