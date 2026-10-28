<script>
(function(){
  var IDLE_MS = 90000, PING_MS = 10000;
  var lastInteract = Date.now(), activeMs = 0, lastTick = Date.now();
  ['click','keydown','input','scroll','mousemove'].forEach(function(ev){
    window.addEventListener(ev, function(){ lastInteract = Date.now(); }, {passive:true});
  });
  setInterval(function(){
    var now = Date.now();
    if (document.visibilityState === 'visible' && (now - lastInteract) < IDLE_MS) {
      activeMs += (now - lastTick);
    }
    lastTick = now;
    var secs = Math.floor(activeMs/1000);
    if (secs > 0) {
      navigator.sendBeacon('app/includes/active_time.php', new Blob([secs], {type:'text/plain'}));
    }
  }, PING_MS);
})();
</script>
