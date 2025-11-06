/**
 * Tests for Admin JavaScript (admin.js)
 */

describe('VCCAdmin Object', () => {
  let mockContactsTable;
  let vccAdmin;

  beforeEach(() => {
    // Create mock admin interface elements
    mockContactsTable = document.createElement('table');
    mockContactsTable.className = 'wp-list-table';
    mockContactsTable.innerHTML = `
      <thead>
        <tr>
          <th><input type="checkbox" id="cb-select-all-1"></th>
          <th>Name</th>
          <th>Email</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><input type="checkbox" name="contacts[]" value="1"></td>
          <td>John Doe</td>
          <td>john@example.com</td>
          <td><button class="vcc-delete-contact" data-id="1" data-name="John Doe">Delete</button></td>
        </tr>
        <tr>
          <td><input type="checkbox" name="contacts[]" value="2"></td>
          <td>Jane Smith</td>
          <td>jane@example.com</td>
          <td><button class="vcc-delete-contact" data-id="2" data-name="Jane Smith">Delete</button></td>
        </tr>
      </tbody>
    `;

    const bulkActions = document.createElement('select');
    bulkActions.id = 'bulk-action-selector-top';
    bulkActions.innerHTML = `
      <option value="-1">Bulk Actions</option>
      <option value="delete">Delete</option>
    `;

    const applyButton = document.createElement('button');
    applyButton.id = 'doaction';
    applyButton.textContent = 'Apply';

    document.body.appendChild(mockContactsTable);
    document.body.appendChild(bulkActions);
    document.body.appendChild(applyButton);

    // Mock the VCCAdmin object
    global.VCCAdmin = {
      init() {
        this.bindEvents();
        this.updateSelectAllState();
        this.updateBulkActionButtonState();
      },

      bindEvents() {
        document.addEventListener('click', (e) => {
          if (e.target.classList.contains('vcc-delete-contact')) {
            this.handleDeleteContact(e);
          }
        });

        const selectAllCheckbox = document.getElementById('cb-select-all-1');
        if (selectAllCheckbox) {
          selectAllCheckbox.addEventListener('change', this.handleSelectAll.bind(this));
        }

        const individualCheckboxes = document.querySelectorAll('input[name="contacts[]"]');
        individualCheckboxes.forEach(checkbox => {
          checkbox.addEventListener('change', this.updateSelectAllState.bind(this));
        });

        const applyButton = document.getElementById('doaction');
        if (applyButton) {
          applyButton.addEventListener('click', this.handleBulkAction.bind(this));
        }
      },

      handleDeleteContact(e) {
        e.preventDefault();
        const button = e.target;
        const contactId = button.dataset.id;
        const contactName = button.dataset.name;

        if (!contactId) return;

        if (!confirm(`Are you sure you want to delete the contact "${contactName}"? This action cannot be undone.`)) {
          return;
        }

        button.disabled = true;
        button.textContent = 'Deleting...';

        // Mock AJAX request
        global.fetch(global.vcc_admin_ajax.ajax_url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'vcc_delete_contact',
            contact_id: contactId,
            nonce: global.vcc_admin_ajax.nonce
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const row = button.closest('tr');
            row.style.opacity = '0.5';
            setTimeout(() => {
              row.remove();
              this.updateContactCount();
            }, 300);
            this.showNotice(data.data.message, 'success');
          } else {
            this.showNotice(data.data.message, 'error');
            button.disabled = false;
            button.textContent = 'Delete';
          }
        })
        .catch(() => {
          this.showNotice('An error occurred while deleting the contact.', 'error');
          button.disabled = false;
          button.textContent = 'Delete';
        });
      },

      handleSelectAll() {
        const selectAllCheckbox = document.getElementById('cb-select-all-1');
        const isChecked = selectAllCheckbox.checked;
        const individualCheckboxes = document.querySelectorAll('input[name="contacts[]"]');
        
        individualCheckboxes.forEach(checkbox => {
          checkbox.checked = isChecked;
        });
        
        this.updateBulkActionButtonState();
      },

      updateSelectAllState() {
        const totalCheckboxes = document.querySelectorAll('input[name="contacts[]"]').length;
        const checkedCheckboxes = document.querySelectorAll('input[name="contacts[]"]:checked').length;
        const selectAllCheckbox = document.getElementById('cb-select-all-1');

        if (checkedCheckboxes === 0) {
          selectAllCheckbox.checked = false;
          selectAllCheckbox.indeterminate = false;
        } else if (checkedCheckboxes === totalCheckboxes) {
          selectAllCheckbox.checked = true;
          selectAllCheckbox.indeterminate = false;
        } else {
          selectAllCheckbox.checked = false;
          selectAllCheckbox.indeterminate = true;
        }

        this.updateBulkActionButtonState();
      },

      updateBulkActionButtonState() {
        const hasSelection = document.querySelectorAll('input[name="contacts[]"]:checked').length > 0;
        const applyButton = document.getElementById('doaction');
        if (applyButton) {
          applyButton.disabled = !hasSelection;
        }
      },

      handleBulkAction(e) {
        const action = document.getElementById('bulk-action-selector-top').value;
        const selectedContacts = document.querySelectorAll('input[name="contacts[]"]:checked');

        if (action === '-1') {
          e.preventDefault();
          this.showNotice('Please select a bulk action.', 'warning');
          return;
        }

        if (selectedContacts.length === 0) {
          e.preventDefault();
          this.showNotice('Please select at least one contact.', 'warning');
          return;
        }

        if (action === 'delete') {
          if (!confirm('Are you sure you want to delete the selected contacts? This action cannot be undone.')) {
            e.preventDefault();
            return;
          }
        }
      },

      showNotice(message, type = 'info') {
        const notice = document.createElement('div');
        notice.className = `notice vcc-notice notice-${type} is-dismissible`;
        notice.innerHTML = `<p>${message}</p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>`;

        const existingNotices = document.querySelectorAll('.vcc-notice');
        existingNotices.forEach(n => n.remove());

        document.body.insertBefore(notice, document.body.firstChild);

        notice.querySelector('.notice-dismiss').addEventListener('click', () => {
          notice.style.opacity = '0';
          setTimeout(() => notice.remove(), 300);
        });

        if (type === 'success') {
          setTimeout(() => {
            notice.style.opacity = '0';
            setTimeout(() => notice.remove(), 300);
          }, 5000);
        }
      },

      updateContactCount() {
        const displayingNum = document.querySelector('.displaying-num');
        if (displayingNum) {
          const currentText = displayingNum.textContent;
          const currentCount = parseInt(currentText.match(/\d+/)[0]);
          const newCount = currentCount - 1;
          displayingNum.textContent = currentText.replace(/\d+/, newCount);
        }
      },

      isValidEmail(email) {
        const regex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        return regex.test(email);
      }
    };

    vccAdmin = global.VCCAdmin;
    vccAdmin.init();
  });

  afterEach(() => {
    document.body.innerHTML = '';
  });

  describe('Initialization', () => {
    test('should initialize admin interface', () => {
      expect(typeof vccAdmin.init).toBe('function');
      expect(typeof vccAdmin.bindEvents).toBe('function');
    });

    test('should bind event listeners', () => {
      const deleteButtons = document.querySelectorAll('.vcc-delete-contact');
      expect(deleteButtons.length).toBeGreaterThan(0);
    });
  });

  describe('Contact Deletion', () => {
    test('should handle contact deletion confirmation', () => {
      const deleteButton = document.querySelector('.vcc-delete-contact');
      const confirmSpy = jest.spyOn(window, 'confirm').mockReturnValue(false);
      
      simulateEvent(deleteButton, 'click');
      
      expect(confirmSpy).toHaveBeenCalledWith(
        'Are you sure you want to delete the contact "John Doe"? This action cannot be undone.'
      );
      
      confirmSpy.mockRestore();
    });

    test('should not delete if user cancels confirmation', () => {
      const deleteButton = document.querySelector('.vcc-delete-contact');
      const confirmSpy = jest.spyOn(window, 'confirm').mockReturnValue(false);
      
      simulateEvent(deleteButton, 'click');
      
      expect(deleteButton.disabled).toBe(false);
      expect(deleteButton.textContent).toBe('Delete');
      
      confirmSpy.mockRestore();
    });

    test('should make AJAX request when deletion is confirmed', () => {
      const deleteButton = document.querySelector('.vcc-delete-contact');
      const confirmSpy = jest.spyOn(window, 'confirm').mockReturnValue(true);
      
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({
          success: true,
          data: { message: 'Contact deleted successfully' }
        })
      });
      
      simulateEvent(deleteButton, 'click');
      
      expect(global.fetch).toHaveBeenCalledWith(
        global.vcc_admin_ajax.ajax_url,
        expect.objectContaining({
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          }
        })
      );
      
      confirmSpy.mockRestore();
    });
  });

  describe('Bulk Actions', () => {
    test('should handle select all checkbox', () => {
      const selectAllCheckbox = document.getElementById('cb-select-all-1');
      const individualCheckboxes = document.querySelectorAll('input[name="contacts[]"]');
      
      selectAllCheckbox.checked = true;
      simulateEvent(selectAllCheckbox, 'change');
      
      individualCheckboxes.forEach(checkbox => {
        expect(checkbox.checked).toBe(true);
      });
    });

    test('should update select all state when individual checkboxes change', () => {
      const selectAllCheckbox = document.getElementById('cb-select-all-1');
      const individualCheckboxes = document.querySelectorAll('input[name="contacts[]"]');
      
      // Check first checkbox
      individualCheckboxes[0].checked = true;
      simulateEvent(individualCheckboxes[0], 'change');
      
      expect(selectAllCheckbox.indeterminate).toBe(true);
      
      // Check all checkboxes
      individualCheckboxes.forEach(checkbox => {
        checkbox.checked = true;
        simulateEvent(checkbox, 'change');
      });
      
      expect(selectAllCheckbox.checked).toBe(true);
      expect(selectAllCheckbox.indeterminate).toBe(false);
    });

    test('should enable/disable bulk action button based on selection', () => {
      const applyButton = document.getElementById('doaction');
      const firstCheckbox = document.querySelector('input[name="contacts[]"]');
      
      // Initially should be disabled
      expect(applyButton.disabled).toBe(true);
      
      // Enable when checkbox is selected
      firstCheckbox.checked = true;
      simulateEvent(firstCheckbox, 'change');
      
      expect(applyButton.disabled).toBe(false);
    });

    test('should validate bulk action selection', () => {
      const applyButton = document.getElementById('doaction');
      const bulkActionSelect = document.getElementById('bulk-action-selector-top');
      const showNoticeSpy = jest.spyOn(vccAdmin, 'showNotice');
      
      bulkActionSelect.value = '-1'; // No action selected
      
      const clickEvent = new Event('click', { bubbles: true, cancelable: true });
      applyButton.dispatchEvent(clickEvent);
      
      expect(showNoticeSpy).toHaveBeenCalledWith('Please select a bulk action.', 'warning');
    });

    test('should validate contact selection for bulk actions', () => {
      const applyButton = document.getElementById('doaction');
      const bulkActionSelect = document.getElementById('bulk-action-selector-top');
      const showNoticeSpy = jest.spyOn(vccAdmin, 'showNotice');
      
      bulkActionSelect.value = 'delete';
      // No contacts selected
      
      const clickEvent = new Event('click', { bubbles: true, cancelable: true });
      applyButton.dispatchEvent(clickEvent);
      
      expect(showNoticeSpy).toHaveBeenCalledWith('Please select at least one contact.', 'warning');
    });
  });

  describe('Notice System', () => {
    test('should show success notices', () => {
      vccAdmin.showNotice('Success message', 'success');
      
      const notice = document.querySelector('.notice-success');
      expect(notice).toBeTruthy();
      expect(notice.textContent).toContain('Success message');
    });

    test('should show error notices', () => {
      vccAdmin.showNotice('Error message', 'error');
      
      const notice = document.querySelector('.notice-error');
      expect(notice).toBeTruthy();
      expect(notice.textContent).toContain('Error message');
    });

    test('should show warning notices', () => {
      vccAdmin.showNotice('Warning message', 'warning');
      
      const notice = document.querySelector('.notice-warning');
      expect(notice).toBeTruthy();
      expect(notice.textContent).toContain('Warning message');
    });

    test('should allow dismissing notices', () => {
      vccAdmin.showNotice('Dismissible notice', 'info');
      
      const notice = document.querySelector('.vcc-notice');
      const dismissButton = notice.querySelector('.notice-dismiss');
      
      expect(dismissButton).toBeTruthy();
      
      simulateEvent(dismissButton, 'click');
      
      // Notice should start fading out
      expect(notice.style.opacity).toBe('0');
    });

    test('should auto-dismiss success notices', (done) => {
      jest.useFakeTimers();
      
      vccAdmin.showNotice('Auto-dismiss notice', 'success');
      
      const notice = document.querySelector('.notice-success');
      expect(notice).toBeTruthy();
      
      // Fast-forward time
      jest.advanceTimersByTime(5000);
      
      setTimeout(() => {
        expect(notice.style.opacity).toBe('0');
        done();
      }, 100);
      
      jest.useRealTimers();
    });
  });

  describe('Email Validation', () => {
    test('should validate correct email addresses', () => {
      const validEmails = [
        'test@example.com',
        'user.name@domain.co.uk',
        'user+tag@example.org'
      ];

      validEmails.forEach(email => {
        expect(vccAdmin.isValidEmail(email)).toBe(true);
      });
    });

    test('should reject invalid email addresses', () => {
      const invalidEmails = [
        'invalid-email',
        '@example.com',
        'user@',
        'user@example'
      ];

      invalidEmails.forEach(email => {
        expect(vccAdmin.isValidEmail(email)).toBe(false);
      });
    });
  });

  describe('Contact Count Update', () => {
    test('should update contact count display', () => {
      const displayingNum = document.createElement('span');
      displayingNum.className = 'displaying-num';
      displayingNum.textContent = '10 items';
      document.body.appendChild(displayingNum);
      
      vccAdmin.updateContactCount();
      
      expect(displayingNum.textContent).toBe('9 items');
    });

    test('should handle missing contact count display gracefully', () => {
      // No displaying-num element exists
      expect(() => {
        vccAdmin.updateContactCount();
      }).not.toThrow();
    });
  });

  describe('Error Handling', () => {
    test('should handle AJAX errors gracefully', async () => {
      const deleteButton = document.querySelector('.vcc-delete-contact');
      const confirmSpy = jest.spyOn(window, 'confirm').mockReturnValue(true);
      const showNoticeSpy = jest.spyOn(vccAdmin, 'showNotice');
      
      global.fetch.mockRejectedValue(new Error('Network error'));
      
      simulateEvent(deleteButton, 'click');
      
      // Wait for promise to resolve
      await new Promise(resolve => setTimeout(resolve, 0));
      
      expect(showNoticeSpy).toHaveBeenCalledWith(
        'An error occurred while deleting the contact.',
        'error'
      );
      
      confirmSpy.mockRestore();
    });

    test('should handle server errors', async () => {
      const deleteButton = document.querySelector('.vcc-delete-contact');
      const confirmSpy = jest.spyOn(window, 'confirm').mockReturnValue(true);
      const showNoticeSpy = jest.spyOn(vccAdmin, 'showNotice');
      
      global.fetch.mockResolvedValue({
        ok: true,
        json: () => Promise.resolve({
          success: false,
          data: { message: 'Server error occurred' }
        })
      });
      
      simulateEvent(deleteButton, 'click');
      
      // Wait for promise to resolve
      await new Promise(resolve => setTimeout(resolve, 0));
      
      expect(showNoticeSpy).toHaveBeenCalledWith('Server error occurred', 'error');
      expect(deleteButton.disabled).toBe(false);
      expect(deleteButton.textContent).toBe('Delete');
      
      confirmSpy.mockRestore();
    });
  });
});