@extends('layouts.auth')

@section('title', 'Login')

@section('content')

<div class="sat-card">

  <div class="sat-header d-flex justify-content-between align-items-center">
    <div>
      <h2>STUDENT ADMISSION TERMINAL</h2>
      <h4 class="text-primary mt-2">USER ID: {{ Auth::user()->user_id }}</h4>
      <p>
        Establish your identity node according to global standards.
        Official processing via <b>connect@unicou.uk</b>
      </p>
    </div>
    <button type="button" class="btn btn-outline-primary btn-sm" onclick="fillStudentDemoData()">
      <i class="fas fa-magic"></i> Auto Fill
    </button>
  </div>


  <div class="wizard-steps mb-4">
    <div class="wizard-step active" data-step="1"><span>✓</span><small>PERSONAL</small></div>
    <div class="wizard-step" data-step="2"><span>2</span><small>CONTACT</small></div>
    <div class="wizard-step" data-step="3"><span>3</span><small>ADDRESS</small></div>
    <div class="wizard-step" data-step="4"><span>4</span><small>UPLOAD</small></div>
    <div class="wizard-step" data-step="5"><span>5</span><small>PURPOSE</small></div>
    <div class="wizard-step" data-step="6"><span>6</span><small>EDUCATION</small></div>
    <div class="wizard-step" data-step="7"><span>7</span><small>COUNTRIES</small></div>
    <div class="wizard-step" data-step="8"><span>8</span><small>BANK</small></div>
    <div class="wizard-step" data-step="9"><span>9</span><small>VERIFY</small></div>
  </div>





  @if ($errors->any())
  <div class="alert alert-danger mx-4 mt-3 mb-0">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
      <li>{{ is_array($error) ? implode(', ', $error) : $error }}</li>
      @endforeach
    </ul>
  </div>
  @endif

  <form id="satForm" method="POST" action="{{ route('auth.form.student.post') }}" enctype="multipart/form-data">
    @csrf
    <!-- ================= PERSONAL ================= -->
    <section class="sat-section form-step active" data-step="1">
      <h4><span>01</span> Personal Information</h4>

      <div class="sat-grid-2">
        <div class="sat-field">
          <label>Full Name *</label>
          <input type="text" name="full_name" placeholder="e.g. Muhammad Ali Khan" value="{{ Auth::user()->name }}" required>
        </div>

        <div class="sat-field">
          <label>Date of Birth (as per ID) *</label>
          <input type="date" name="dob" required>
        </div>

        <div class="sat-field">
          <label>ID Document Type *</label>
          <select name="id_type" required>
            <option value="">Select document type</option>
            <option>National ID Card</option>
            <option>Passport</option>
            <option>Driving License</option>
          </select>
        </div>

        <div class="sat-field">
          <label>ID Document No *</label>
          <input type="text" name="id_number" placeholder="e.g. CNIC / Passport Number" required>
        </div>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <button type="button" class="btn btn-light prev-btn">← Back</button>
        <button type="button" class="btn btn-primary next-btn">Continue →</button>
      </div>
    </section>

    <!-- ================= CONTACT ================= -->
    <section class="sat-section form-step" data-step="2">
      <h4><span>02</span> Contact Information</h4>

      <div class="sat-grid-2">
        <div class="sat-field">
          <label>Primary Contact No *</label>
          <input type="tel" id="primary_contact_input" name="primary_contact_dummy" placeholder="+92 3XX XXX XXXX" value="{{ Auth::user()->phone }}" required>
          <input type="hidden" name="primary_contact" id="primary_contact" value="{{ Auth::user()->phone }}">
        </div>

        <div class="sat-field">
          <label>Email (Voucher Delivery) *</label>
          <input type="email" name="email" placeholder="e.g. student@email.com" value="{{ Auth::user()->email }}" required>
        </div>

        <div class="sat-field">
          <label>WhatsApp No *</label>
          <input type="tel" id="whatsapp_number_input" name="whatsapp_number_dummy" placeholder="+92 3XX XXX XXXX" required>
          <input type="hidden" name="whatsapp_number" id="whatsapp_number">
        </div>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <button type="button" class="btn btn-light prev-btn">← Back</button>
        <button type="button" class="btn btn-primary next-btn">Continue →</button>
      </div>
    </section>

    <!-- ================= ADDRESS ================= -->
    <section class="sat-section form-step" data-step="3">
      <h4><span>03</span> Address Details</h4>

      <div class="sat-grid-2">
        <div class="sat-field sat-full">
          <label>Detailed Address *</label>
          <input type="text" name="address" placeholder="House, Street, Area, Sector" required>
        </div>

        <div class="sat-field">
          <label>Country *</label>
          <select name="country" id="country" required></select>
        </div>

        <div class="sat-field">
          <label>State / Province *</label>
          <select name="state" id="state" required></select>
        </div>

        <div class="sat-field">
          <label>City *</label>
          <select name="city" id="city" required></select>
        </div>

        <div class="sat-field">
          <label>Post Code *</label>
          <input type="text" name="post_code" placeholder="e.g. 54000" required>
        </div>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <button type="button" class="btn btn-light prev-btn">← Back</button>
        <button type="button" class="btn btn-primary next-btn">Continue →</button>
      </div>
    </section>

    <!-- ================= DOCUMENT UPLOAD ================= -->
    <section class="sat-section form-step" data-step="4">
      <h4><span>04</span> Upload Documents</h4>

      <div class="alert alert-warning mb-3" style="font-size: 0.85rem;">
        <i class="fas fa-exclamation-circle me-1"></i> <strong>Important:</strong> Your name, address, and date of birth in this form <b>MUST EXACTLY MATCH</b> the details on your uploaded ID documents. Discrepancies will lead to automatic rejection by ShuftiPro.
      </div>

      <div class="sat-grid-2">
        <div class="sat-field sat-full">
          <label>Upload ID Document * (PDF, JPG, PNG - Max 5MB)</label>
          <input type="file" name="id_doc" accept=".pdf,.jpg,.jpeg,.png" required>
        </div>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <button type="button" class="btn btn-light prev-btn">← Back</button>
        <button type="button" class="btn btn-primary next-btn">Continue →</button>
      </div>
    </section>

    <!-- ================= EXAM PURPOSE ================= -->
    <section class="sat-section form-step" data-step="5">
      <h4><span>05</span> English Exam Purpose</h4>

      <div class="sat-grid-2">
        <div class="sat-field">
          <select name="exam_purpose" required>
            <option value="">Select exam purpose</option>
            <option>Education</option>
            <option>Migration</option>
            <option>Other</option>
          </select>
        </div>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <button type="button" class="btn btn-light prev-btn">← Back</button>
        <button type="button" class="btn btn-primary next-btn">Continue →</button>
      </div>
    </section>

    <!-- ================= EDUCATION ================= -->
    <section class="sat-section form-step" data-step="6">
      <h4><span>06</span> Education Details</h4>

      <div class="sat-grid-2">
        <div class="sat-field">
          <label>Highest Education *</label>
          <input type="text" name="highest_education" placeholder="e.g. Bachelor of Science" required>
        </div>

        <div class="sat-field">
          <label>Passing Year *</label>
          <input type="number" name="passing_year" placeholder="e.g. 2022" min="1990" max="2035" required>
        </div>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <button type="button" class="btn btn-light prev-btn">← Back</button>
        <button type="button" class="btn btn-primary next-btn">Continue →</button>
      </div>
    </section>

    <!-- ================= COUNTRIES ================= -->
    <section class="sat-section form-step" data-step="7">
      <h4><span>07</span> Preferred Countries</h4>

      <div class="sat-grid-2 Preferred-Country">
        <label class="sat-consent Preferred-Country_checkbox"><input type="checkbox" name="preferred_countries[]" value="United Kingdom"> United Kingdom</label>
        <label class="sat-consent Preferred-Country_checkbox"><input type="checkbox" name="preferred_countries[]" value="United States"> United States</label>
        <label class="sat-consent Preferred-Country_checkbox"><input type="checkbox" name="preferred_countries[]" value="Canada"> Canada</label>
        <label class="sat-consent Preferred-Country_checkbox"><input type="checkbox" name="preferred_countries[]" value="Australia"> Australia</label>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <button type="button" class="btn btn-light prev-btn">← Back</button>
        <button type="button" class="btn btn-primary next-btn">Continue →</button>
      </div>
    </section>

    <!-- ================= BANK ================= -->
    <section class="sat-section form-step" data-step="8">
      <h4><span>08</span> Bank Account Details</h4>

      <div class="sat-grid-2">
        <div class="sat-field">
          <label>Bank Name *</label>
          <input type="text" name="bank_name" placeholder="e.g. Standard Chartered Bank" required>
        </div>

        <div class="sat-field">
          <label>Bank Country *</label>
          <input type="text" name="bank_country" placeholder="e.g. Pakistan" required>
        </div>

        <div class="sat-field sat-full">
          <label>IBAN / BSB / Account No *</label>
          <input type="text" name="account_number" placeholder="e.g. PK36SCBL0000001123456702" required>
        </div>
      </div>


      <div class="d-flex justify-content-between mt-4">
        <button type="button" class="btn btn-light prev-btn">← Back</button>
        <button type="button" class="btn btn-primary next-btn">Continue →</button>
      </div>
    </section>

    <!-- ================= FINAL UPLOAD ================= -->
    <section class="sat-section form-step" data-step="9">
      <h4><span>09</span> Final Verification Upload</h4>

      <div class="sat-grid-2">
        <div class="sat-field sat-full">
          <label>Re-Upload ID Document * (PDF, JPG, PNG - Max 5MB)</label>
          <input type="file" name="id_doc_final" accept=".pdf,.jpg,.jpeg,.png" required>
        </div>
      </div>

      <!-- ================= CONSENT ================= -->
      <div class="sat-consent">
        <input type="checkbox" required id="consent_policy">
        <p>
          I agree to the Non-Refundable & "Undisclosed" Voucher Policy.
        </p>
      </div>

      <div class="sat-consent">
        <input type="checkbox" required id="consent_terms">
        <p>
          I accept the <a href="javascript:void(0)" class="text-primary fw-bold" data-bs-toggle="modal" data-bs-target="#termsModal">Terms & Conditions</a> and confirm all submitted information is correct.
        </p>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <button type="button" class="btn btn-light prev-btn">← Back</button>
      </div>

      <button type="submit" class="sat-btn mt-4">
        INITIALIZE REGISTRY SYNC →
      </button>
    </section>

  </form>

</div>
</div>

<!-- Terms and Conditions Modal -->
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <div class="terms-content">
          <h6 class="fw-bold mb-3">1. Declaration of Authenticity & Truth</h6>
          <p>I declare that all personal credentials and ID documents provided are authentic. Any misrepresentation grants UniCou the right to terminate my account immediately and void any purchased vouchers as a penalty.</p>

          <h6 class="fw-bold mb-3">2. Account Security & Credential Protection</h6>
          <ul class="mb-3">
            <li><strong>Confidentiality:</strong> I am solely responsible for maintaining the security of my account login and passwords.</li>
            <li><strong>Personal Liability:</strong> I accept full legal and financial liability for all transactions made through my account.</li>
          </ul>

          <h6 class="fw-bold mb-3">3. Non-Refundable & "Undisclosed" Voucher Policy</h6>
          <ul class="mb-3">
            <li><strong>Final Sale:</strong> I acknowledge that exam vouchers (PTE, IELTS, TOEFL, etc.) are digital assets and are strictly non-refundable and non-exchangeable once issued to my account.</li>
            <li><strong>Undisclosed Status:</strong> This policy applies regardless of whether the voucher remains Undisclosed. Once the voucher code is generated and delivered to my portal, the transaction is considered consumed and final.</li>
            <li><strong>No Reversals:</strong> I agree that I cannot request a refund for unused, expired, or mistakenly purchased vouchers.</li>
          </ul>

          <h6 class="fw-bold mb-3">4. Payment Warranty & Anti-Fraud Protection</h6>
          <ul class="mb-3">
            <li><strong>Fraud Consequences:</strong> In the event of a "Fake Payment" or chargeback, UniCou is authorized to instantly deactivate all associated vouchers.</li>
            <li><strong>Result Invalidation:</strong> I agree that UniCou may notify Pearson, IDP, British Council, or ETS of the fraud, leading to the cancellation of test results.</li>
          </ul>

          <h6 class="fw-bold mb-3">5. Irrevocable Indemnity Bond</h6>
          <p>I hereby agree to indemnify and hold UniCou harmless against any losses, third-party claims, or damages arising from my negligence or fraudulent activity.</p>

          <h6 class="fw-bold mb-3">6. Settlement & Currency</h6>
          <p>Payments are settled via UniCou's authorized payment gateways. I am responsible for all local bank fees and currency conversion costs.</p>
        </div>
      </div>
      <div class="modal-footer bg-light">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
      </div>
    </div>
  </div>
</div>

<script>
  window.loggedInUser = {
    email: "{{ Auth::user()->email }}",
    name: "{{ Auth::user()->name }}",
    phone: "{{ Auth::user()->phone }}"
  };
</script>


@push('scripts')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const primaryContactInput = window.intlTelInput(document.querySelector("#primary_contact_input"), {
      utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
      separateDialCode: true,
      initialCountry: "auto",
      dropdownContainer: document.body,
      geoIpLookup: function(success, failure) {
        $.get("https://ipinfo.io", function() {}, "jsonp").always(function(resp) {
          var countryCode = (resp && resp.country) ? resp.country : "us";
          success(countryCode);
        });
      },
    });

    const whatsappNumberInput = window.intlTelInput(document.querySelector("#whatsapp_number_input"), {
      utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
      separateDialCode: true,
      initialCountry: "auto",
      dropdownContainer: document.body,
      geoIpLookup: function(success, failure) {
        $.get("https://ipinfo.io", function() {}, "jsonp").always(function(resp) {
          var countryCode = (resp && resp.country) ? resp.country : "us";
          success(countryCode);
        });
      },
    });

    // Real-time validation
    function validateField(input) {
      const $input = $(input);
      const name = $input.attr('name');
      let isValid = true;
      let errorMessage = "";

      if ($input.prop('required')) {
        if ($input.attr('type') === 'checkbox') {
          if (!$input.prop('checked')) {
            isValid = false;
            errorMessage = "Required";
          }
        } else if ($input.attr('type') !== 'file' && !$input.val()) {
          isValid = false;
          errorMessage = "Required";
        }
      }

      if (isValid && $input.attr('type') === 'email' && $input.val()) {
        const emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
        if (!emailReg.test($input.val())) {
          isValid = false;
          errorMessage = "Invalid email";
        }
      }

      if (isValid && $input.attr('name') === 'dob' && $input.val()) {
        const dob = new Date($input.val());
        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const m = today.getMonth() - dob.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
          age--;
        }
        if (age < 16) {
          isValid = false;
          errorMessage = "Must be at least 16 years old.";
        }
      }

      if (isValid && $input.attr('type') === 'file') {
        const file = input.files[0];
        if ($input.prop('required') && !file) {
          isValid = false;
          errorMessage = "File is required";
        } else if (file) {
          const fileSize = file.size / 1024 / 1024; // in MB
          const fileName = file.name.toLowerCase();
          const ext = fileName.split('.').pop();

          if (name === 'id_doc' || name === 'id_doc_final') {
            if (!['pdf', 'jpg', 'jpeg', 'png'].includes(ext)) {
              isValid = false;
              errorMessage = "Invalid file type. Allowed: PDF, JPG, PNG";
            } else if (fileSize > 5) {
              isValid = false;
              errorMessage = "File size must be under 5MB";
            }
          }
        }
      }

      if (isValid && $input.attr('type') === 'tel') {
        const digitsOnly = /^\d+$/;
        const val = $input.val().replace(/\s+/g, '').replace('+', '');
        if (val && !digitsOnly.test(val)) {
          isValid = false;
          errorMessage = "Only numbers allowed";
        }
      }

      if (!isValid) {
        $input.addClass('is-invalid');
        if ($input.closest('.sat-field').find('.invalid-feedback').length === 0) {
          $input.after(`<div class="invalid-feedback">${errorMessage}</div>`);
        } else {
          $input.closest('.sat-field').find('.invalid-feedback').text(errorMessage);
        }
      } else {
        $input.removeClass('is-invalid');
        $input.closest('.sat-field').find('.invalid-feedback').remove();
      }
      return isValid;
    }

    $('#satForm input, #satForm select').on('blur change input', function() {
      validateField(this);
    });

    document.getElementById('satForm').addEventListener('submit', function(e) {
      let isFormValid = true;
      $('#satForm [required], #satForm input[type="file"], #satForm input[type="tel"]').each(function() {
        if (!validateField(this)) {
          isFormValid = false;
        }
      });

      // ITI validation
      if (!primaryContactInput.isValidNumber()) {
        isFormValid = false;
        $('#primary_contact_input').addClass('is-invalid');
        if ($('#primary_contact_input').closest('.sat-field').find('.invalid-feedback').length === 0) {
          $('#primary_contact_input').after('<div class="invalid-feedback">Invalid number for selected country</div>');
        }
      }
      if (!whatsappNumberInput.isValidNumber()) {
        isFormValid = false;
        $('#whatsapp_number_input').addClass('is-invalid');
        if ($('#whatsapp_number_input').closest('.sat-field').find('.invalid-feedback').length === 0) {
          $('#whatsapp_number_input').after('<div class="invalid-feedback">Invalid number for selected country</div>');
        }
      }

      if (!isFormValid) {
        e.preventDefault();
        Swal.fire('Error', 'Please fix form errors before submitting.', 'error');
        return;
      }

      // Sync full numbers
      $('#primary_contact').val(primaryContactInput.getNumber());
      $('#whatsapp_number').val(whatsappNumberInput.getNumber());

      const btn = this.querySelector('.sat-btn');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> VERIFYING...';
      }
    });
  });

  function fillStudentDemoData() {
    const form = document.getElementById('satForm');
    form.querySelector('input[name="full_name"]').value = window.loggedInUser.name || '';
    form.querySelector('input[name="dob"]').value = "1998-05-15";
    form.querySelector('select[name="id_type"]').value = "National ID Card";
    form.querySelector('input[name="id_number"]').value = "35201-1234567-1";
    form.querySelector('input[name="primary_contact_dummy"]').value = "3001234567";
    form.querySelector('input[name="email"]').value = "ali.khan-demo@email.com";
    form.querySelector('input[name="whatsapp_number_dummy"]').value = "3001234567";
    form.querySelector('input[name="address"]').value = "House 45, Street 12, DHA Phase 5";
    form.querySelector('input[name="post_code"]').value = "54000";
    form.querySelector('select[name="exam_purpose"]').value = "Education";
    form.querySelector('input[name="highest_education"]').value = "Bachelor of Science";
    form.querySelector('input[name="passing_year"]').value = "2022";
    form.querySelectorAll('input[name="preferred_countries[]"]').forEach((cb, idx) => {
      if (idx < 2) cb.checked = true;
    });
    form.querySelector('input[name="bank_name"]').value = "Standard Chartered Bank";
    form.querySelector('input[name="bank_country"]').value = "Pakistan";
    form.querySelector('input[name="account_number"]').value = "PK36SCBL0000001123456702";
    document.getElementById('consent_policy').checked = true;
    document.getElementById('consent_terms').checked = true;
  }
</script>

<script type="module">
  import {
    Country,
    State,
    City
  } from "https://cdn.jsdelivr.net/npm/country-state-city@3.2.1/+esm";

  document.addEventListener("DOMContentLoaded", function() {
    const countrySelect = document.getElementById("country");
    const stateSelect = document.getElementById("state");
    const citySelect = document.getElementById("city");

    // Set default options
    countrySelect.innerHTML = '<option value="">Select Country</option>';
    stateSelect.innerHTML = '<option value="">Select State</option>';
    citySelect.innerHTML = '<option value="">Select City</option>';

    // Populate Countries
    const countries = Country.getAllCountries();
    countries.forEach(country => {
      const option = document.createElement('option');
      option.value = country.name;
      option.textContent = country.name;
      option.dataset.code = country.isoCode;
      countrySelect.appendChild(option);
    });

    // Country Change Event
    countrySelect.addEventListener('change', function() {
      const selectedOption = this.options[this.selectedIndex];
      const countryCode = selectedOption.dataset.code;

      stateSelect.innerHTML = '<option value="">Select State</option>';
      citySelect.innerHTML = '<option value="">Select City</option>';

      if (countryCode) {
        const states = State.getStatesOfCountry(countryCode);
        states.forEach(state => {
          const option = document.createElement('option');
          option.value = state.name;
          option.textContent = state.name;
          option.dataset.code = state.isoCode;
          stateSelect.appendChild(option);
        });
      }
    });

    // State Change Event
    stateSelect.addEventListener('change', function() {
      const countryCode = countrySelect.options[countrySelect.selectedIndex].dataset.code;
      const stateCode = this.options[this.selectedIndex].dataset.code;

      citySelect.innerHTML = '<option value="">Select City</option>';

      if (countryCode && stateCode) {
        const cities = City.getCitiesOfState(countryCode, stateCode);
        cities.forEach(city => {
          const option = document.createElement('option');
          option.value = city.name;
          option.textContent = city.name;
          citySelect.appendChild(option);
        });
      }
    });
  });
</script>


<script>
  let currentStep = parseInt(localStorage.getItem('studentStep')) || 1;
  if (currentStep < 1 || currentStep > 9) currentStep = 1;

  const steps = document.querySelectorAll('.form-step');
  const indicators = document.querySelectorAll('.wizard-step');

  function showStep(step) {
    if (step < 1 || step > 9) return;

    steps.forEach(s => s.classList.remove('active'));

    indicators.forEach(i => {
      i.classList.remove('active');
      const span = i.querySelector('span');
      if (span) span.innerText = i.dataset.step;
    });

    const targetStep = document.querySelector(`.form-step[data-step="${step}"]`);
    if (targetStep) targetStep.classList.add('active');

    const activeIndicator = document.querySelector(`.wizard-step[data-step="${step}"]`);
    if (activeIndicator) {
      activeIndicator.classList.add('active');
      const span = activeIndicator.querySelector('span');
      if (span) span.innerText = "✓";
    }

    localStorage.setItem('studentStep', step);
    currentStep = step;
    window.scrollTo({
      top: 0,
      behavior: 'smooth'
    });
  }

  function validateStep(step) {
    const currentSection = document.querySelector(`.form-step[data-step="${step}"]`);
    if (!currentSection) return true;

    const inputs = currentSection.querySelectorAll('input[required], select[required]');
    let valid = true;
    inputs.forEach(input => {
      if (!input.value || (input.type === 'checkbox' && !input.checked)) {
        input.classList.add('is-invalid');
        valid = false;
      } else {
        input.classList.remove('is-invalid');
      }
    });

    if (!valid) {
      alert('Please fill all required fields before continuing.');
    }
    return valid;
  }

  showStep(currentStep);

  document.querySelectorAll('.next-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      if (validateStep(currentStep)) {
        showStep(currentStep + 1);
      }
    });
  });

  document.querySelectorAll('.prev-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      if (currentStep > 1) {
        showStep(currentStep - 1);
      }
    });
  });
</script>

@endpush
@endsection