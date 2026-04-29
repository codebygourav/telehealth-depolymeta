<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration Full Flow Tester</title>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #475569;
            --border: #cbd5e1;
            --primary: #0369a1;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            max-width: 1050px;
            margin-inline: auto;
        }
        h1 { margin: 0 0 6px; font-size: 24px; }
        .hint { color: var(--muted); margin: 0 0 18px; }
        section {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 12px;
        }
        h2 { margin: 0 0 8px; font-size: 16px; color: var(--primary); }
        .row {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 10px;
        }
        label { display: block; margin-top: 6px; font-size: 12px; color: var(--muted); }
        input, select, textarea, button {
            width: 100%;
            margin-top: 4px;
            padding: 8px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 14px;
            background: #fff;
            color: #0f172a;
        }
        textarea { min-height: 68px; resize: vertical; }
        button {
            width: auto;
            background: var(--primary);
            color: #fff;
            border-color: #0c4a6e;
            cursor: pointer;
            margin-top: 10px;
            padding-inline: 14px;
        }
        button.secondary { background: #475569; border-color: #334155; }
        button:disabled, select:disabled { opacity: 0.55; cursor: not-allowed; }
        .inline { display: flex; align-items: center; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
        .inline input[type="checkbox"] { width: auto; margin-top: 0; }
        pre {
            margin: 8px 0 0;
            background: #020617;
            color: #e2e8f0;
            border-radius: 8px;
            border: 1px solid #334155;
            padding: 10px;
            font-size: 12px;
            overflow: auto;
            max-height: 280px;
        }
    </style>
</head>
<body>
<h1>Patient Registration Full Flow Tester</h1>
<p class="hint">
    Flow: Register email → Verify OTP → Complete Profile → Book Slot → Razorpay → Verify Payment.
</p>

<section>
    <h2>API Base URLs</h2>
    <div class="row">
        <div>
            <label for="authBase">Auth APIs</label>
            <input id="authBase" value="{{ url('/api/v2/auth') }}">
        </div>
        <div>
            <label for="appBase">App APIs (verify-payment)</label>
            <input id="appBase" value="{{ url('/api/v2') }}">
        </div>
    </div>
</section>

<section>
    <h2>1) Register New Email</h2>
    <label for="regEmail">Email</label>
    <input id="regEmail" type="email" placeholder="newuser@example.com">
    <button type="button" id="btnRegister">POST /auth/register</button>
    <pre id="outRegister"></pre>
</section>

<section>
    <h2>2) Verify Email OTP</h2>
    <div class="row">
        <div>
            <label for="verifyEmail">Email</label>
            <input id="verifyEmail" type="email">
        </div>
        <div>
            <label for="verifyOtp">OTP</label>
            <input id="verifyOtp" placeholder="Enter OTP from email">
        </div>
    </div>
    <button type="button" id="btnVerifyEmail">POST /auth/verify-email</button>
    <button type="button" class="secondary" id="btnStatus">GET /auth/status</button>
    <pre id="outVerifyEmail"></pre>
</section>

<section>
    <h2>3) Load Department → Doctor → Slot Availability</h2>
    <button type="button" id="btnDepartments">GET /auth/registration/departments</button>
    <div class="row">
        <div>
            <label for="selDepartment">Department</label>
            <select id="selDepartment">
                <option value="">— load departments first —</option>
            </select>
        </div>
        <div>
            <label>&nbsp;</label>
            <button type="button" id="btnDoctors" disabled>GET /auth/registration/doctors</button>
        </div>
    </div>
    <div class="row">
        <div>
            <label for="selDoctor">Doctor</label>
            <select id="selDoctor">
                <option value="">— choose department first —</option>
            </select>
        </div>
        <div>
            <label for="fromDate">from_date (optional)</label>
            <input id="fromDate" type="date">
        </div>
        <div>
            <label for="toDate">to_date (optional)</label>
            <input id="toDate" type="date">
        </div>
        <div>
            <label>&nbsp;</label>
            <button type="button" id="btnAvailability" disabled>GET /auth/registration/doctor-availability</button>
        </div>
    </div>
    <div class="row">
        <div>
            <label for="selSlotDate">Slot Date</label>
            <select id="selSlotDate" disabled>
                <option value="">— load availability first —</option>
            </select>
        </div>
        <div>
            <label for="selSlotTime">Slot Time</label>
            <select id="selSlotTime" disabled>
                <option value="">— select date first —</option>
            </select>
        </div>
        <div>
            <label for="selSlotType">Slot Type</label>
            <select id="selSlotType" disabled>
                <option value="">— select time first —</option>
            </select>
        </div>
    </div>
    <pre id="outAvailability"></pre>
</section>

<section>
    <h2>4) Complete Profile + Optional Booking</h2>
    <div class="row">
        <div><label>Email *</label><input id="f_email" type="email"></div>
        <div><label>Password *</label><input id="f_password" type="password"></div>
        <div><label>First Name *</label><input id="f_first_name" value="Test"></div>
        <div><label>Last Name *</label><input id="f_last_name" value="Patient"></div>
        <div>
            <label>Gender *</label>
            <select id="f_gender">
                <option value="male">male</option>
                <option value="female">female</option>
                <option value="other">other</option>
            </select>
        </div>
        <div><label>Date of Birth *</label><input id="f_dob" type="date"></div>
        <div><label>Mobile *</label><input id="f_mobile" value="9999999999"></div>
        <div><label>Alternate</label><input id="f_alternate"></div>
        <div><label>Blood Group</label><input id="f_blood" placeholder="A+"></div>
        <div>
            <label>Marital Status</label>
            <select id="f_marital">
                <option value="">—</option>
                <option value="single">single</option>
                <option value="married">married</option>
                <option value="divorced">divorced</option>
                <option value="widowed">widowed</option>
            </select>
        </div>
    </div>
    <label>Address</label><textarea id="f_address"></textarea>
    <div class="row">
        <div><label>Allergies</label><textarea id="f_allergies"></textarea></div>
        <div><label>Existing Conditions</label><textarea id="f_conditions"></textarea></div>
    </div>
    <label>Current Medications</label><textarea id="f_meds"></textarea>
    <label>Past Medical History</label><textarea id="f_history"></textarea>
    <div class="row">
        <div><label>Emergency Contact Name</label><input id="f_ec_name"></div>
        <div><label>Emergency Relationship</label><input id="f_ec_rel"></div>
        <div><label>Emergency Phone</label><input id="f_ec_phone"></div>
    </div>
    <div class="row">
        <div><label>Insurance Provider</label><input id="f_ins_pr"></div>
        <div><label>Policy Number</label><input id="f_ins_no"></div>
        <div><label>Policy Expiry</label><input id="f_ins_exp" type="date"></div>
        <div><label>TPA Details</label><input id="f_ins_tpa"></div>
    </div>
    <label>Visit Reason</label>
    <textarea id="f_visit_reason" placeholder="Reason for this appointment"></textarea>

    <div class="inline">
        <input type="checkbox" id="f_consent" checked>
        <label for="f_consent" style="margin:0;">treatment_consent_accepted</label>
    </div>
    <div class="inline">
        <input type="checkbox" id="f_book" checked>
        <label for="f_book" style="margin:0;">book_appointment using selected slot</label>
    </div>

    <button type="button" id="btnComplete">POST /auth/complete-profile</button>
    <pre id="outComplete"></pre>
</section>

<section>
    <h2>5) Razorpay + Verify Payment</h2>
    <button type="button" class="secondary" id="btnRazorpay" disabled>Open Razorpay for last booking</button>
    <div class="row">
        <div><label>Bearer Token</label><input id="bearerToken"></div>
        <div><label>appointment_id</label><input id="v_app_id"></div>
        <div><label>razorpay_order_id</label><input id="v_order_id"></div>
    </div>
    <div class="row">
        <div><label>razorpay_payment_id</label><input id="v_pay_id"></div>
        <div><label>razorpay_signature</label><input id="v_sig"></div>
    </div>
    <button type="button" class="secondary" id="btnVerifyPayment">POST /verify-payment</button>
    <pre id="outPayment"></pre>
</section>

<script>
const $ = (id) => document.getElementById(id);
const authBase = () => $('authBase').value.replace(/\/$/, '');
const appBase = () => $('appBase').value.replace(/\/$/, '');

let selectedSlot = null;
let allSlotsFlat = [];
let currentDoctorId = null;
let lastBookingPayment = null;

const pretty = (x) => { try { return JSON.stringify(x, null, 2); } catch { return String(x); } };

async function apiGet(url) {
    const r = await fetch(url, { headers: { Accept: 'application/json' } });
    const t = await r.text();
    try { return { ok: r.ok, status: r.status, data: JSON.parse(t) }; }
    catch { return { ok: r.ok, status: r.status, data: t }; }
}
async function apiPost(url, body, token = null) {
    const headers = { Accept: 'application/json', 'Content-Type': 'application/json' };
    if (token) headers.Authorization = 'Bearer ' + token;
    const r = await fetch(url, { method: 'POST', headers, body: JSON.stringify(body) });
    const t = await r.text();
    try { return { ok: r.ok, status: r.status, data: JSON.parse(t) }; }
    catch { return { ok: r.ok, status: r.status, data: t }; }
}

function syncEmailEverywhere(email) {
    $('verifyEmail').value = email || '';
    $('f_email').value = email || '';
}

function resetSlots() {
    selectedSlot = null;
    allSlotsFlat = [];
    currentDoctorId = null;
    $('selSlotDate').innerHTML = '<option value="">— load availability first —</option>';
    $('selSlotDate').disabled = true;
    $('selSlotTime').innerHTML = '<option value="">— select date first —</option>';
    $('selSlotTime').disabled = true;
    $('selSlotType').innerHTML = '<option value="">— select time first —</option>';
    $('selSlotType').disabled = true;
}

function slotTypeLabel(s) {
    let txt = s.consultation_type_label || (s.consultation_type === 'video' ? 'Video' : 'In-person');
    if (s.consultation_type === 'in-person' && s.opd_type) txt += ' / ' + s.opd_type + ' OPD';
    if (s.consultation_fee !== null && s.consultation_fee !== undefined && s.consultation_fee !== '') txt += ' (₹' + s.consultation_fee + ')';
    return txt;
}

function applySlotById(slotId) {
    const s = allSlotsFlat.find((x) => x.id === slotId);
    if (!s || !currentDoctorId || !s.booking_start_time) { selectedSlot = null; return; }
    selectedSlot = {
        availability_id: s.id,
        appointment_date: s.date,
        appointment_time: s.booking_start_time,
        consultation_type: s.consultation_type,
        opd_type: s.opd_type || null,
        doctor_id: currentDoctorId,
    };
}

function flattenAvailability(res) {
    resetSlots();
    const data = res.data && res.data.data;
    if (!data || !Array.isArray(data.availability_by_date)) return;
    currentDoctorId = data.doctor_id;
    for (const day of data.availability_by_date) {
        for (const slot of day.slots || []) {
            allSlotsFlat.push({
                id: slot.id,
                date: day.date,
                booking_start_time: slot.booking_start_time || null,
                start_time: slot.start_time || null,
                end_time: slot.end_time || null,
                consultation_type: slot.consultation_type,
                consultation_type_label: slot.consultation_type_label,
                opd_type: slot.opd_type || null,
                consultation_fee: slot.consultation_fee,
            });
        }
    }

    const dates = [...new Set(allSlotsFlat.map((s) => s.date).filter(Boolean))].sort();
    const dSel = $('selSlotDate');
    dSel.innerHTML = '<option value="">— select date —</option>';
    for (const d of dates) {
        const o = document.createElement('option');
        o.value = d;
        o.textContent = d;
        dSel.appendChild(o);
    }
    dSel.disabled = dates.length === 0;
}

$('selSlotDate').addEventListener('change', () => {
    selectedSlot = null;
    const date = $('selSlotDate').value;
    const tSel = $('selSlotTime');
    const typeSel = $('selSlotType');
    typeSel.innerHTML = '<option value="">— select time first —</option>';
    typeSel.disabled = true;
    tSel.innerHTML = '<option value="">— select time —</option>';
    if (!date) { tSel.disabled = true; return; }
    const rows = allSlotsFlat.filter((s) => s.date === date);
    const timeMap = new Map();
    for (const r of rows) {
        if (!r.booking_start_time) continue;
        if (!timeMap.has(r.booking_start_time)) {
            const label = (r.start_time && r.end_time) ? (r.start_time + ' - ' + r.end_time) : r.booking_start_time;
            timeMap.set(r.booking_start_time, label);
        }
    }
    const keys = [...timeMap.keys()].sort();
    for (const k of keys) {
        const o = document.createElement('option');
        o.value = k;
        o.textContent = timeMap.get(k);
        tSel.appendChild(o);
    }
    tSel.disabled = keys.length === 0;
});

$('selSlotTime').addEventListener('change', () => {
    selectedSlot = null;
    const date = $('selSlotDate').value;
    const time = $('selSlotTime').value;
    const typeSel = $('selSlotType');
    typeSel.innerHTML = '<option value="">— select slot type —</option>';
    if (!date || !time) { typeSel.disabled = true; return; }
    const rows = allSlotsFlat.filter((s) => s.date === date && s.booking_start_time === time);
    for (const r of rows) {
        const o = document.createElement('option');
        o.value = r.id;
        o.textContent = slotTypeLabel(r);
        typeSel.appendChild(o);
    }
    typeSel.disabled = rows.length === 0;
    if (rows.length === 1) {
        typeSel.value = rows[0].id;
        applySlotById(rows[0].id);
    }
});

$('selSlotType').addEventListener('change', () => {
    applySlotById($('selSlotType').value);
});

$('btnRegister').onclick = async () => {
    const email = $('regEmail').value.trim();
    syncEmailEverywhere(email);
    const res = await apiPost(authBase() + '/register', { email });
    $('outRegister').textContent = pretty(res);
};

$('btnVerifyEmail').onclick = async () => {
    const email = $('verifyEmail').value.trim();
    syncEmailEverywhere(email);
    const otp = $('verifyOtp').value.trim();
    const res = await apiPost(authBase() + '/verify-email', { email, otp });
    $('outVerifyEmail').textContent = pretty(res);
};

$('btnStatus').onclick = async () => {
    const email = encodeURIComponent($('verifyEmail').value.trim());
    const res = await apiGet(authBase() + '/status?email=' + email);
    $('outVerifyEmail').textContent = pretty(res);
};

$('btnDepartments').onclick = async () => {
    const res = await apiGet(authBase() + '/registration/departments');
    $('outAvailability').textContent = pretty(res);
    const sel = $('selDepartment');
    sel.innerHTML = '<option value="">— select department —</option>';
    const arr = res.data && res.data.data && res.data.data.departments ? res.data.data.departments : [];
    for (const d of arr) {
        const o = document.createElement('option');
        o.value = d.id;
        o.textContent = d.name;
        sel.appendChild(o);
    }
    $('btnDoctors').disabled = false;
};

$('btnDoctors').onclick = async () => {
    const dept = $('selDepartment').value;
    if (!dept) { alert('Select department first'); return; }
    const res = await apiGet(authBase() + '/registration/doctors?department_id=' + encodeURIComponent(dept));
    $('outAvailability').textContent = pretty(res);
    const sel = $('selDoctor');
    sel.innerHTML = '<option value="">— select doctor —</option>';
    const arr = res.data && res.data.data && res.data.data.doctors ? res.data.data.doctors : [];
    for (const d of arr) {
        const o = document.createElement('option');
        o.value = d.id;
        o.textContent = d.name || ((d.first_name || '') + ' ' + (d.last_name || '')).trim();
        sel.appendChild(o);
    }
    $('btnAvailability').disabled = false;
    resetSlots();
};

$('selDoctor').addEventListener('change', resetSlots);

$('btnAvailability').onclick = async () => {
    const doc = $('selDoctor').value;
    if (!doc) { alert('Select doctor first'); return; }
    let q = 'doctor_id=' + encodeURIComponent(doc);
    if ($('fromDate').value) q += '&from_date=' + encodeURIComponent($('fromDate').value);
    if ($('toDate').value) q += '&to_date=' + encodeURIComponent($('toDate').value);
    const res = await apiGet(authBase() + '/registration/doctor-availability?' + q);
    $('outAvailability').textContent = pretty(res);
    flattenAvailability(res);
};

$('btnComplete').onclick = async () => {
    const body = {
        email: $('f_email').value.trim(),
        password: $('f_password').value,
        first_name: $('f_first_name').value.trim(),
        last_name: $('f_last_name').value.trim(),
        gender: $('f_gender').value,
        date_of_birth: $('f_dob').value,
        mobile_no: $('f_mobile').value.trim(),
        alternate_no: $('f_alternate').value || null,
        address: $('f_address').value || null,
        blood_group: $('f_blood').value || null,
        marital_status: $('f_marital').value || null,
        allergies: $('f_allergies').value || null,
        existing_conditions: $('f_conditions').value || null,
        current_medications: $('f_meds').value || null,
        past_medical_history: $('f_history').value || null,
        emergency_contact_name: $('f_ec_name').value || null,
        emergency_contact_relationship: $('f_ec_rel').value || null,
        emergency_contact_phone: $('f_ec_phone').value || null,
        insurance_provider: $('f_ins_pr').value || null,
        insurance_policy_number: $('f_ins_no').value || null,
        insurance_policy_expiry: $('f_ins_exp').value || null,
        insurance_tpa_details: $('f_ins_tpa').value || null,
        treatment_consent_accepted: $('f_consent').checked,
        visit_reason: $('f_visit_reason').value || null,
    };

    const dep = $('selDepartment').value;
    if (dep) body.department_id = dep;

    if ($('f_book').checked) {
        if (!selectedSlot) {
            alert('Select slot date, time and slot type first.');
            return;
        }
        body.book_appointment = true;
        body.doctor_id = selectedSlot.doctor_id;
        body.availability_id = selectedSlot.availability_id;
        body.appointment_date = selectedSlot.appointment_date;
        body.appointment_time = selectedSlot.appointment_time;
        body.consultation_type = selectedSlot.consultation_type;
        if (selectedSlot.consultation_type === 'in-person') {
            body.opd_type = selectedSlot.opd_type || 'general';
        }
    } else {
        body.book_appointment = false;
    }

    const res = await apiPost(authBase() + '/complete-profile', body);
    $('outComplete').textContent = pretty(res);

    const token = res.data && res.data.token ? res.data.token : null;
    if (token) $('bearerToken').value = token;

    const data = res.data && res.data.data ? res.data.data : null;
    const app = data && data.appointment ? data.appointment : null;
    if (app && app.id) $('v_app_id').value = app.id;

    const booking = data && data.booking_payment ? data.booking_payment : null;
    lastBookingPayment = booking;
    if (booking && booking.order_id) {
        $('v_order_id').value = booking.order_id;
    }
    $('btnRazorpay').disabled = !(res.ok && booking && booking.order_id && typeof Razorpay !== 'undefined');
};

$('btnRazorpay').onclick = () => {
    const pay = lastBookingPayment;
    if (!pay || !pay.order_id || !pay.razorpay_key_id) {
        $('outPayment').textContent = 'No razorpay order details available from last complete-profile response.';
        return;
    }
    const amountPaise = pay.amount_paise != null ? pay.amount_paise : Math.round((pay.amount_rupees || 0) * 100);
    const options = {
        key: pay.razorpay_key_id,
        amount: amountPaise,
        currency: 'INR',
        order_id: pay.order_id,
        name: 'Appointment Booking',
        handler: function (response) {
            $('v_pay_id').value = response.razorpay_payment_id || '';
            $('v_order_id').value = response.razorpay_order_id || pay.order_id;
            $('v_sig').value = response.razorpay_signature || '';
            $('outPayment').textContent = pretty({ checkout_success: true, response });
        },
    };
    try {
        new Razorpay(options).open();
    } catch (e) {
        $('outPayment').textContent = String(e);
    }
};

$('btnVerifyPayment').onclick = async () => {
    const token = $('bearerToken').value.trim();
    const body = {
        appointment_id: $('v_app_id').value.trim(),
        razorpay_order_id: $('v_order_id').value.trim(),
        razorpay_payment_id: $('v_pay_id').value.trim(),
    };
    const sig = $('v_sig').value.trim();
    if (sig) body.razorpay_signature = sig;
    const res = await apiPost(appBase() + '/verify-payment', body, token);
    $('outPayment').textContent = pretty(res);
};

// convenience defaults
(() => {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    $('f_dob').value = `${yyyy - 25}-${mm}-${dd}`;
    $('fromDate').value = `${yyyy}-${mm}-${dd}`;
})();
</script>
</body>
</html>
