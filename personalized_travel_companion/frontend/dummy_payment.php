<?php
// dummy_payment.php - Simulated Secure Payment Modal & Trip Confirmed Dialogue Box
?>
<div id="paymentModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.55); z-index:1000; justify-content:center; align-items:center; backdrop-filter:blur(3px); padding: 16px; box-sizing: border-box;">
    <div style="background:#fff; padding:24px 20px; border-radius:14px; width:100%; max-width:420px; box-sizing:border-box; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
            <h3 style="margin:0; font-size:20px; color:#183c2b;">Simulated Secure Payment</h3>
            <button type="button" onclick="closePaymentModal()" style="background:none; border:none; font-size:24px; cursor:pointer; color:#68776e; line-height:1;" aria-label="Close">&times;</button>
        </div>
        <p class="muted" style="font-size:13px; margin:0 0 20px; color:#68776e;">Complete your booking simulation to confirm your trip.</p>

        <form method="POST" id="simulatedPaymentForm">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="confirm">
            <input type="hidden" name="ajax" value="1">

            <div style="display:flex; flex-direction:column; gap:12px; margin-bottom:20px;">
                <label style="font-size:13px; font-weight:bold; color:#23352b;">
                    Cardholder Name
                    <input type="text" value="<?= htmlspecialchars($_SESSION['user']['full_name'] ?? 'Traveler') ?>" required style="width:100%; box-sizing:border-box; padding:9px 12px; margin-top:4px; border:1px solid #ccd8ce; border-radius:6px; font-size:14px;">
                </label>

                <label style="font-size:13px; font-weight:bold; color:#23352b;">
                    Card Number (Demo)
                    <input type="text" value="4242 •••• •••• 4242" required style="width:100%; box-sizing:border-box; padding:9px 12px; margin-top:4px; border:1px solid #ccd8ce; border-radius:6px; font-size:14px;">
                </label>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <label style="font-size:13px; font-weight:bold; color:#23352b;">
                        Expiry Date
                        <input type="text" value="12/28" required style="width:100%; box-sizing:border-box; padding:9px 12px; margin-top:4px; border:1px solid #ccd8ce; border-radius:6px; font-size:14px;">
                    </label>
                    <label style="font-size:13px; font-weight:bold; color:#23352b;">
                        CVV
                        <input type="password" value="123" maxlength="3" required style="width:100%; box-sizing:border-box; padding:9px 12px; margin-top:4px; border:1px solid #ccd8ce; border-radius:6px; font-size:14px;">
                    </label>
                </div>
            </div>

            <div style="display:flex; gap:10px;">
                <button type="submit" id="btnPayConfirm" class="btn" style="flex:1; padding:11px 16px; background:#1f7a4d; color:#fff; border:none; border-radius:6px; font-weight:bold; cursor:pointer; font-size:14px; transition: background 0.2s ease;">
                    Pay &amp; Confirm Trip
                </button>
                <button type="button" onclick="closePaymentModal()" style="padding:11px 16px; background:#e9efeb; color:#23352b; border:none; border-radius:6px; font-weight:bold; cursor:pointer; font-size:14px;">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Trip Confirmed Dialogue Box Modal -->
<div id="tripConfirmedModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.65); z-index:1100; justify-content:center; align-items:center; backdrop-filter:blur(4px); padding: 16px; box-sizing: border-box;">
    <div style="background:#fff; padding:30px 20px; border-radius:16px; width:100%; max-width:440px; box-sizing:border-box; box-shadow:0 12px 40px rgba(0,0,0,0.25); text-align:center;">
        <div style="width:72px; height:72px; margin:0 auto 16px; background:#e8f7ee; border:3px solid #1f7a4d; border-radius:50%; display:flex; align-items:center; justify-content:center;">
            <svg style="width:40px; height:40px; color:#1f7a4d;" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        </div>
        <h2 style="margin:0 0 8px; font-size:24px; color:#183c2b; font-weight:800;">Trip Confirmed!</h2>
        <p style="margin:0 0 16px; font-size:14px; color:#55695d; line-height:1.5;">
            Payment simulation successful! Your trip to <strong style="color:#183c2b;"><?= e($trip['destination_name'] ?? 'your destination') ?></strong> has been officially confirmed.
        </p>
        <div style="background:#f4f9f6; border:1px solid #d8e8dc; border-radius:10px; padding:14px 16px; margin-bottom:20px; font-size:13px; color:#263b2e; text-align:left;">
            <div style="margin-bottom: 4px;"><strong>Trip Title:</strong> <?= e($trip['title'] ?? 'Personalized Journey') ?></div>
            <div style="margin-bottom: 4px;"><strong>Status:</strong> <span style="color:#059669; font-weight:700;">Confirmed (<?= e(ucfirst($trip['status'] === 'draft' ? 'Active' : $trip['status'])) ?>)</span></div>
            <div style="color:#5b6e62;"><strong>Redirecting:</strong> <span id="redirectText">Taking you to My Trips in 2 seconds...</span></div>
        </div>
        <a href="my_trips.php" id="linkToMyTrips" class="btn" style="display:block; width:100%; box-sizing:border-box; padding:12px; background:#1f7a4d; color:#fff; text-decoration:none; border-radius:8px; font-weight:bold; font-size:14px; text-align:center;">
            Go to My Trips &rarr;
        </a>
    </div>
</div>

<script>
function openPaymentModal() {
    document.getElementById('paymentModal').style.display = 'flex';
}
function closePaymentModal() {
    document.getElementById('paymentModal').style.display = 'none';
}

document.getElementById('simulatedPaymentForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnPayConfirm');
    btn.disabled = true;
    btn.textContent = 'Processing Payment...';

    const formData = new FormData(this);
    fetch('itinerary.php?id=<?= (int) ($id ?? 0) ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        closePaymentModal();
        const confModal = document.getElementById('tripConfirmedModal');
        confModal.style.display = 'flex';

        let seconds = 2;
        const redirText = document.getElementById('redirectText');
        const timer = setInterval(() => {
            seconds--;
            if (seconds <= 0) {
                clearInterval(timer);
                window.location.href = data.redirect || 'my_trips.php';
            } else {
                redirText.textContent = `Taking you to My Trips in ${seconds} second${seconds === 1 ? '' : 's'}...`;
            }
        }, 800);
    })
    .catch(() => {
        document.getElementById('simulatedPaymentForm').submit();
    });
});
</script>
