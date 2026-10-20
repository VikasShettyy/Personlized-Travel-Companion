<?php
// dummy_payment.php - Simulated Secure Payment Modal
?>
<div id="paymentModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:30px; border-radius:12px; width:100%; max-width:400px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
        <h3 style="margin-top:0; font-size:20px;">Simulated Secure Payment</h3>
        <p class="muted" style="font-size:13px; margin-bottom:20px;">Complete your booking simulation for this trip.</p>

        <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="confirm">

            <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:20px;">
                <label style="font-size:13px; font-weight:bold;">
                    Cardholder Name
                    <input type="text" value="<?= htmlspecialchars($_SESSION['user']['name'] ?? 'Traveler') ?>" required style="width:100%; padding:8px; margin-top:4px; border:1px solid #ccd8ce; border-radius:6px;">
                </label>

                <label style="font-size:13px; font-weight:bold;">
                    Card Number (Dummy)
                    <input type="text" value="4242 •••• •••• 4242" required style="width:100%; padding:8px; margin-top:4px; border:1px solid #ccd8ce; border-radius:6px;">
                </label>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <label style="font-size:13px; font-weight:bold;">
                        Expiry Date
                        <input type="text" value="12/28" required style="width:100%; padding:8px; margin-top:4px; border:1px solid #ccd8ce; border-radius:6px;">
                    </label>
                    <label style="font-size:13px; font-weight:bold;">
                        CVV
                        <input type="password" value="123" maxlength="3" required style="width:100%; padding:8px; margin-top:4px; border:1px solid #ccd8ce; border-radius:6px;">
                    </label>
                </div>
            </div>

            <div style="display:flex; gap:10px;">
                <button type="submit" class="btn" style="flex:1; padding:10px; background:#247653; color:#fff; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Pay & Confirm Trip</button>
                <button type="button" onclick="closePaymentModal()" style="padding:10px 15px; background:#e9efeb; color:#23352b; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPaymentModal() {
    document.getElementById('paymentModal').style.display = 'flex';
}
function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
}
</script>