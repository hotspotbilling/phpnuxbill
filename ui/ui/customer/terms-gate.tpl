{* "I agree to the Terms and Conditions" box. The register button stays off until it is ticked (the server checks it too).
   One copy for every register template: {include file="customer/terms-gate.tpl"} *}
{if $_c['register_terms'] neq 'no'}
<div class="form-group terms-agree">
    <label class="checkbox-container terms-check">
        <input type="checkbox" name="agree_terms" value="yes" id="agree_terms">
        <span class="checkmark"></span>
        <span>{Lang::T('I agree to the')} <a href="{Text::url('terms')}" target="_blank" rel="noopener">{Lang::T('Terms and Conditions')}</a></span>
    </label>
</div>
<script>
(function () {
    var box = document.getElementById("agree_terms"), form = box && box.closest("form");
    if (!form) { return; }
    function sync() {
        var list = form.querySelectorAll("button[type=submit],input[type=submit]");
        for (var i = 0; i < list.length; i++) {
            var b = list[i];
            if (!box.checked) {
                b.disabled = true; b.classList.add("terms-blocked"); b.title = '{Lang::T('Agree to the Terms and Conditions first')|escape:'javascript'}';
            } else if (b.classList.contains("terms-blocked")) {
                b.classList.remove("terms-blocked"); b.removeAttribute("title");
                if (!b.classList.contains("captcha-blocked") && !b.classList.contains("loading")) { b.disabled = false; }
            }
        }
    }
    box.addEventListener("change", sync);
    form.addEventListener("submit", function (e) { if (!box.checked) { e.preventDefault(); e.stopImmediatePropagation(); } }, true);
    sync();
    setTimeout(sync, 400); // after the captcha script has had its first look
})();
</script>
{/if}
