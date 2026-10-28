<?php /* Herbruikbaar: maakt ALLE <input type="date"> velden klikbaar over het hele vlak. Inladen via require_once in layout of per scherm. */ ?>
<style>
input[type="date"]{cursor:pointer;}
input[type="date"]::-webkit-calendar-picker-indicator{opacity:.6;}
</style>
<script>
(function(){
  function bind(el){
    if(el.dataset.dpBound) return;
    el.dataset.dpBound="1";
    el.addEventListener("click", function(){
      if(typeof this.showPicker==="function"){ try{ this.showPicker(); }catch(e){} }
    });
  }
  function bindAll(){ document.querySelectorAll('input[type="date"]').forEach(bind); }
  if(document.readyState==="loading"){ document.addEventListener("DOMContentLoaded", bindAll); }
  else { bindAll(); }
})();
</script>
