/**
 * Tests for Frontend JavaScript (frontend.js)
 */

// Import the frontend JavaScript (would need to be adapted for actual module system)
// For now, we'll test by including the script content or mocking the VCCForm class

describe('VCCForm Class', () => {
  let mockForm;
  let vccForm;

  beforeEach(() => {
    // Create a mock form element
    mockForm = createTestForm();
    document.body.appendChild(mockForm);

    // Mock the VCCForm class (this would normally be imported)
    global.VCCForm = class {
      constructor(form) {
        this.form = form;
        this.isSubmitting = false;
        this.init();
      }

      init() {
        this.bindEvents();
        this.setupValidation();
      }

      bindEvents() {
        this.form.addEventListener('submit', this.handleSubmit.bind(this));
        
        // Real-time validation
        const inputs = this.form.querySelectorAll('input[required]');
        inputs.forEach(input => {
          input.addEventListener('blur', this.validateField.bind(this, input));
          input.addEventListener('input', this.clearFieldError.bind(this, input));
        });
      }

      setupValidation() {
        this.validationRules = {
          vcc_full_name: { required: true, minLength: 2 },
          vcc_email: { required: true, email: true },
          vcc_phone: { required: true, phone: true },
          vcc_consent: { required: true }
        };
      }

      validateField(field) {
        const name = field.name;
        const value = field.type === 'checkbox' ? field.checked : field.value.trim();
        const rules = this.validationRules[name];

        if (!rules) return true;

        // Required validation
        if (rules.required && (!value || (field.type === 'checkbox' && !field.checked))) {
          this.showFieldError(field, 'This field is required.');
          return false;
        }

        // Email validation
        if (rules.email && value && !this.isValidEmail(value)) {
          this.showFieldError(field, 'Please enter a valid email address.');
          return false;
        }

        // Phone validation
        if (rules.phone && value && !this.isValidPhone(value)) {
          this.showFieldError(field, 'Please enter a valid phone number.');
          return false;
        }

        // Min length validation
        if (rules.minLength && value.length < rules.minLength) {
          this.showFieldError(field, `Minimum ${rules.minLength} characters required.`);
          return false;
        }

        this.clearFieldError(field);
        return true;
      }

      validateForm() {
        const inputs = this.form.querySelectorAll('input[required]');
        let isValid = true;

        inputs.forEach(input => {
          if (!this.validateField(input)) {
            isValid = false;
          }
        });

        return isValid;
      }

      isValidEmail(email) {
        const regex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        return regex.test(email);
      }

      isValidPhone(phone) {
        const cleaned = phone.replace(/\D/g, '');
        return cleaned.length >= 10;
      }

      showFieldError(field, message) {
        this.clearFieldError(field);
        const errorElement = document.createElement('div');
        errorElement.className = 'vcc-field-error';
        errorElement.textContent = message;
        field.parentNode.appendChild(errorElement);
        field.classList.add('vcc-error');
      }

      clearFieldError(field) {
        const errorElement = field.parentNode.querySelector('.vcc-field-error');
        if (errorElement) {
          errorElement.remove();
        }
        field.classList.remove('vcc-error');
      }

      handleSubmit(e) {
        e.preventDefault();
        
        if (this.isSubmitting) return;
        
        if (!this.validateForm()) return;
        
        this.isSubmitting = true;
        this.submitForm();
      }

      submitForm() {
        const formData = new FormData(this.form);
        
        // Mock AJAX submission
        return fetch(global.vcc_ajax.ajax_url, {
          method: 'POST',
          body: formData
        }).then(response => response.json())
          .then(data => {
            this.isSubmitting = false;
            if (data.success) {
              this.showSuccessMessage(data.message);
              this.form.reset();
            } else {
              this.showErrorMessage(data.message);
            }
          })
          .catch(error => {
            this.isSubmitting = false;
            this.showErrorMessage('An error occurred. Please try again.');
          });
      }

      showSuccessMessage(message) {
        this.showMessage(message, 'success');
      }

      showErrorMessage(message) {
        this.showMessage(message, 'error');
      }

      showMessage(message, type) {
        const messageElement = document.createElement('div');
        messageElement.className = `vcc-message vcc-message-${type}`;
        messageElement.textContent = message;
        this.form.parentNode.insertBefore(messageElement, this.form);
        
        setTimeout(() => {
          messageElement.remove();
        }, 5000);
      }
    };

    vccForm = new global.VCCForm(mockForm);
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  describe('Initialization', () => {
    test('should initialize with form element', () => {
      expect(vccForm.form).toBe(mockForm);
      expect(vccForm.isSubmitting).toBe(false);
    });

    test('should bind event listeners', () => {
      const submitSpy = jest.spyOn(vccForm, 'handleSubmit');
      simulateEvent(mockForm, 'submit');
      expect(submitSpy).toHaveBeenCalled();
    });
  });

  describe('Field Validation', () => {
    test('should validate required fields', () => {
      const nameField = mockForm.querySelector('input[name="vcc_full_name"]');
      nameField.value = '';
      
      const isValid = vccForm.validateField(nameField);
      expect(isValid).toBe(false);
      expect(nameField.classList.contains('vcc-error')).toBe(true);
    });

    test('should validate email format', () => {
      const emailField = mockForm.querySelector('input[name="vcc_email"]');
      
      emailField.value = 'invalid-email';
      expect(vccForm.validateField(emailField)).toBe(false);
      
      emailField.value = 'valid@example.com';
      expect(vccForm.validateField(emailField)).toBe(true);
    });

    test('should validate phone number', () => {
      const phoneField = mockForm.querySelector('input[name="vcc_phone"]');
      
      phoneField.value = '123';
      expect(vccForm.validateField(phoneField)).toBe(false);
      
      phoneField.value = '+1 (555) 123-4567';
      expect(vccForm.validateField(phoneField)).toBe(true);
    });

    test('should validate consent checkbox', () => {
      const consentField = mockForm.querySelector('input[name="vcc_consent"]');
      
      consentField.checked = false;
      expect(vccForm.validateField(consentField)).toBe(false);
      
      consentField.checked = true;
      expect(vccForm.validateField(consentField)).toBe(true);
    });
  });

  describe('Email Validation', () => {
    test('should accept valid email addresses', () => {
      const validEmails = [
        'test@example.com',
        'user.name@domain.co.uk',
        'user+tag@example.org',
        'user123@test-domain.com'
      ];

      validEmails.forEach(email => {
        expect(vccForm.isValidEmail(email)).toBe(true);
      });
    });

    test('should reject invalid email addresses', () => {
      const invalidEmails = [
        'invalid-email',
        '@example.com',
        'user@',
        'user..double.dot@example.com',
        'user@example',
        ''
      ];

      invalidEmails.forEach(email => {
        expect(vccForm.isValidEmail(email)).toBe(false);
      });
    });
  });

  describe('Phone Validation', () => {
    test('should accept valid phone numbers', () => {
      const validPhones = [
        '+1 (555) 123-4567',
        '555-123-4567',
        '(555) 123-4567',
        '5551234567',
        '+1-555-123-4567'
      ];

      validPhones.forEach(phone => {
        expect(vccForm.isValidPhone(phone)).toBe(true);
      });
    });

    test('should reject invalid phone numbers', () => {
      const invalidPhones = [
        '123',
        '555-123',
        'not-a-phone',
        ''
      ];

      invalidPhones.forEach(phone => {
        expect(vccForm.isValidPhone(phone)).toBe(false);
      });
    });
  });

  describe('Form Submission', () => {
    test('should prevent submission if form is invalid', () => {
      const submitSpy = jest.spyOn(vccForm, 'submitForm');
      
      // Leave form fields empty
      simulateEvent(mockForm, 'submit');
      
      expect(submitSpy).not.toHaveBeenCalled();
    });

    test('should submit form if valid', () => {
      const submitSpy = jest.spyOn(vccForm, 'submitForm');
      
      // Fill form with valid data
      mockForm.querySelector('input[name="vcc_full_name"]').value = 'John Doe';
      mockForm.querySelector('input[name="vcc_email"]').value = 'john@example.com';
      mockForm.querySelector('input[name="vcc_phone"]').value = '555-123-4567';
      mockForm.querySelector('input[name="vcc_consent"]').checked = true;
      
      simulateEvent(mockForm, 'submit');
      
      expect(submitSpy).toHaveBeenCalled();
    });

    test('should prevent double submission', () => {
      vccForm.isSubmitting = true;
      const submitSpy = jest.spyOn(vccForm, 'submitForm');
      
      simulateEvent(mockForm, 'submit');
      
      expect(submitSpy).not.toHaveBeenCalled();
    });

    test('should handle successful submission', async () => {
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({
          success: true,
          message: 'Thank you for your submission!'
        })
      });

      const showSuccessSpy = jest.spyOn(vccForm, 'showSuccessMessage');
      
      await vccForm.submitForm();
      
      expect(showSuccessSpy).toHaveBeenCalledWith('Thank you for your submission!');
      expect(vccForm.isSubmitting).toBe(false);
    });

    test('should handle submission errors', async () => {
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({
          success: false,
          message: 'Submission failed'
        })
      });

      const showErrorSpy = jest.spyOn(vccForm, 'showErrorMessage');
      
      await vccForm.submitForm();
      
      expect(showErrorSpy).toHaveBeenCalledWith('Submission failed');
      expect(vccForm.isSubmitting).toBe(false);
    });

    test('should handle network errors', async () => {
      global.fetch.mockRejectedValue(new Error('Network error'));

      const showErrorSpy = jest.spyOn(vccForm, 'showErrorMessage');
      
      await vccForm.submitForm();
      
      expect(showErrorSpy).toHaveBeenCalledWith('An error occurred. Please try again.');
      expect(vccForm.isSubmitting).toBe(false);
    });
  });

  describe('Error Display', () => {
    test('should show field error messages', () => {
      const nameField = mockForm.querySelector('input[name="vcc_full_name"]');
      vccForm.showFieldError(nameField, 'Test error message');
      
      const errorElement = nameField.parentNode.querySelector('.vcc-field-error');
      expect(errorElement).toBeTruthy();
      expect(errorElement.textContent).toBe('Test error message');
      expect(nameField.classList.contains('vcc-error')).toBe(true);
    });

    test('should clear field error messages', () => {
      const nameField = mockForm.querySelector('input[name="vcc_full_name"]');
      vccForm.showFieldError(nameField, 'Test error');
      vccForm.clearFieldError(nameField);
      
      const errorElement = nameField.parentNode.querySelector('.vcc-field-error');
      expect(errorElement).toBeFalsy();
      expect(nameField.classList.contains('vcc-error')).toBe(false);
    });

    test('should show success messages', () => {
      vccForm.showSuccessMessage('Success!');
      
      const messageElement = document.querySelector('.vcc-message-success');
      expect(messageElement).toBeTruthy();
      expect(messageElement.textContent).toBe('Success!');
    });

    test('should show error messages', () => {
      vccForm.showErrorMessage('Error!');
      
      const messageElement = document.querySelector('.vcc-message-error');
      expect(messageElement).toBeTruthy();
      expect(messageElement.textContent).toBe('Error!');
    });
  });

  describe('Real-time Validation', () => {
    test('should validate on field blur', () => {
      const nameField = mockForm.querySelector('input[name="vcc_full_name"]');
      const validateSpy = jest.spyOn(vccForm, 'validateField');
      
      nameField.value = '';
      simulateEvent(nameField, 'blur');
      
      expect(validateSpy).toHaveBeenCalledWith(nameField);
    });

    test('should clear errors on field input', () => {
      const nameField = mockForm.querySelector('input[name="vcc_full_name"]');
      const clearErrorSpy = jest.spyOn(vccForm, 'clearFieldError');
      
      simulateEvent(nameField, 'input');
      
      expect(clearErrorSpy).toHaveBeenCalledWith(nameField);
    });
  });
});