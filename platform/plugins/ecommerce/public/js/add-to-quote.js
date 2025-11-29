// Handle add to quote functionality
(function() {
    'use strict';
    
    console.log('Add to Quote script loading...');
    
    // Main handler function
    function handleAddToQuote(e) {
        // Find the button that was clicked
        let button = null;
        const target = e.target || e.srcElement;
        
        // Check if target or parent is our button
        if (target) {
            // Try closest method first
            if (target.closest) {
                button = target.closest('[data-bb-toggle="add-to-quote"]') || 
                         target.closest('.add-to-quote-button');
            }
            
            // Manual traversal if closest doesn't work
            if (!button) {
                let el = target;
                for (let i = 0; i < 10 && el; i++) {
                    if (el.getAttribute && 
                        (el.getAttribute('data-bb-toggle') === 'add-to-quote' ||
                         el.classList && el.classList.contains('add-to-quote-button'))) {
                        button = el;
                        break;
                    }
                    el = el.parentElement || el.parentNode;
                }
            }
        }
        
        if (!button) {
            return; // Not our button
        }
        
        console.log('Add to Quote clicked:', button);
        
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        
        // Get form (optional - for product detail pages)
        let form = button.closest('form');
        if (!form) {
            // Try to find form by searching parents
            let el = button.parentElement;
            for (let i = 0; i < 5 && el; i++) {
                if (el.tagName === 'FORM') {
                    form = el;
                    break;
                }
                el = el.parentElement;
            }
        }
        
        // Get product ID - try multiple sources
        let productId = button.getAttribute('data-product-id') || button.getAttribute('data-id');
        
        if (!productId && form) {
            const hiddenInput = form.querySelector('.hidden-product-id') || form.querySelector('input[name="id"]');
            if (hiddenInput) {
                productId = hiddenInput.value;
            }
        }
        
        if (!productId) {
            console.error('Product ID not found', button);
            alert('Product ID not found. Please refresh the page.');
            return;
        }
        
        // Get quantity - default to 1 if no form
        let quantity = 1;
        if (form) {
            const qtyInput = form.querySelector('input[name="qty"]') || form.querySelector('.qty-input');
            quantity = qtyInput ? parseInt(qtyInput.value) || 1 : 1;
        }
        
        // Get URL
        const url = button.getAttribute('data-url') || 
                   (typeof route !== 'undefined' ? route('public.quote.add-to-quote') : null) ||
                   window.location.origin + '/quote/add-to-quote';
        
        // Get CSRF token - try meta tag first, then form
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                         (form ? form.querySelector('input[name="_token"]')?.value : null);
        
        if (!csrfToken) {
            console.error('CSRF token not found');
            alert('Security token missing. Please refresh the page.');
            return;
        }
        
        // Show loading state
        const originalHTML = button.innerHTML;
        const originalDisabled = button.disabled;
        button.innerHTML = '<i class="icon-spinner icon-spin"></i> Adding...';
        button.disabled = true;
        
        console.log('Sending request:', { id: productId, qty: quantity, url: url });
        
        // Make request
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                id: productId,
                qty: quantity,
                description: ''
            })
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                return response.text().then(text => {
                    throw new Error('HTTP error! status: ' + response.status + ', body: ' + text);
                });
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            
            if (data.error) {
                throw new Error(data.message || 'Error adding product to quote');
            }
            
            // Show success message
            if (typeof window.Botble !== 'undefined' && window.Botble.showNotice) {
                window.Botble.showNotice('success', data.message || 'Product added to quote successfully!');
            } else {
                alert(data.message || 'Product added to quote successfully!');
            }
            
            // Update quote count in header
            if (data.data && data.data.count !== undefined) {
                const quoteCountElements = document.querySelectorAll('.btn-quote span i, .btn-quote span');
                quoteCountElements.forEach(el => {
                    if (el.tagName === 'I') {
                        el.textContent = data.data.count;
                    } else if (el.querySelector('i')) {
                        el.querySelector('i').textContent = data.data.count;
                    }
                });
            }
            
            // Update quote dropdown content and open drawer
            if (data.data && data.data.html) {
                // Find quote dropdown - it's the .ps-cart--mobile sibling of .btn-quote
                document.querySelectorAll('.btn-quote').forEach(btn => {
                    const cartMini = btn.closest('.ps-cart--mini');
                    if (cartMini) {
                        const dropdown = cartMini.querySelector('.ps-cart--mobile');
                        if (dropdown) {
                            dropdown.innerHTML = data.data.html;
                            
                            // Reinitialize lazy loading if available
                            if (typeof Theme !== 'undefined' && Theme.lazyLoadInstance) {
                                Theme.lazyLoadInstance.update();
                            }
                            
                            // Scroll to quote icon if not visible
                            const rect = btn.getBoundingClientRect();
                            const isVisible = rect.top >= 0 && rect.left >= 0 && 
                                            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) && 
                                            rect.right <= (window.innerWidth || document.documentElement.clientWidth);
                            
                            if (!isVisible) {
                                btn.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
                            }
                            
                            // Open the quote drawer automatically
                            // Add active class to trigger drawer opening
                            cartMini.classList.add('active', 'quote-drawer-open');
                            
                            // Ensure dropdown is visible
                            dropdown.style.display = 'block';
                            dropdown.style.opacity = '1';
                            dropdown.style.visibility = 'visible';
                            dropdown.style.zIndex = '9999';
                            
                            // Trigger hover event using jQuery if available (more reliable)
                            if (typeof jQuery !== 'undefined') {
                                jQuery(cartMini).addClass('hover').trigger('mouseenter');
                            } else {
                                // Fallback: trigger native mouseenter event
                                const mouseEnterEvent = new MouseEvent('mouseenter', {
                                    view: window,
                                    bubbles: true,
                                    cancelable: true
                                });
                                cartMini.dispatchEvent(mouseEnterEvent);
                            }
                            
                            // Set up auto-close after 5 seconds or on mouseleave (only if not hovering)
                            let autoCloseTimeout;
                            let isHovering = false;
                            
                            const closeDrawer = () => {
                                if (!isHovering) {
                                    cartMini.classList.remove('active', 'quote-drawer-open', 'hover');
                                    if (typeof jQuery !== 'undefined') {
                                        jQuery(cartMini).removeClass('hover');
                                    }
                                    // Reset inline styles to let CSS hover handle it
                                    dropdown.style.display = '';
                                    dropdown.style.opacity = '';
                                    dropdown.style.visibility = '';
                                    dropdown.style.zIndex = '';
                                }
                                clearTimeout(autoCloseTimeout);
                            };
                            
                            // Auto-close after 5 seconds if not hovering
                            autoCloseTimeout = setTimeout(() => {
                                if (!isHovering) {
                                    closeDrawer();
                                }
                            }, 5000);
                            
                            // Track hover state
                            const handleMouseEnter = () => {
                                isHovering = true;
                                clearTimeout(autoCloseTimeout);
                            };
                            
                            const handleMouseLeave = () => {
                                isHovering = false;
                                // Close after a short delay when mouse leaves
                                setTimeout(() => {
                                    if (!isHovering) {
                                        closeDrawer();
                                    }
                                }, 300);
                            };
                            
                            cartMini.addEventListener('mouseenter', handleMouseEnter);
                            cartMini.addEventListener('mouseleave', handleMouseLeave);
                            dropdown.addEventListener('mouseenter', handleMouseEnter);
                            dropdown.addEventListener('mouseleave', handleMouseLeave);
                        }
                    }
                });
            }
            
            // Trigger custom event
            document.dispatchEvent(new CustomEvent('quote:updated', { detail: data }));
        })
        .catch(error => {
            console.error('Error adding to quote:', error);
            if (typeof window.Botble !== 'undefined' && window.Botble.showNotice) {
                window.Botble.showNotice('error', error.message || 'Error adding product to quote');
            } else {
                alert(error.message || 'Error adding product to quote');
            }
        })
        .finally(() => {
            button.innerHTML = originalHTML;
            button.disabled = originalDisabled;
        });
    }
    
    // Attach event listener
    function attachListeners() {
        console.log('Attaching event listeners...');
        
        // Remove any existing listeners
        document.removeEventListener('click', handleAddToQuote, true);
        document.removeEventListener('click', handleAddToQuote, false);
        
        // Add with capture phase (bubbles down, runs first)
        document.addEventListener('click', handleAddToQuote, true);
        
        // Also attach directly to existing buttons
        const buttons = document.querySelectorAll('[data-bb-toggle="add-to-quote"], .add-to-quote-button');
        console.log('Found', buttons.length, 'quote buttons');
        
        buttons.forEach(button => {
            if (!button.dataset.handlerAttached) {
                button.dataset.handlerAttached = 'true';
                button.addEventListener('click', handleAddToQuote, true);
                console.log('Attached handler to button:', button);
            }
        });
    }
    
    // Initialize immediately and on DOM ready
    console.log(document.readyState);
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attachListeners);
    } else {
        attachListeners();
    }
    
    // Also try after delays for dynamic content
    setTimeout(attachListeners, 100);
    setTimeout(attachListeners, 500);
    setTimeout(attachListeners, 1000);
    
    // Watch for new buttons added to DOM
    if (typeof MutationObserver !== 'undefined' && document.body) {
        const observer = new MutationObserver(function(mutations) {
            let shouldAttach = false;
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        if ((node.hasAttribute && node.hasAttribute('data-bb-toggle') && 
                             node.getAttribute('data-bb-toggle') === 'add-to-quote') ||
                            (node.classList && node.classList.contains('add-to-quote-button')) ||
                            (node.querySelector && node.querySelector('[data-bb-toggle="add-to-quote"], .add-to-quote-button'))) {
                            shouldAttach = true;
                        }
                    }
                });
            });
            if (shouldAttach) {
                setTimeout(attachListeners, 50);
            }
        });
        
        if (document.body) {
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    }
    
    // Export for debugging
    window.handleAddToQuote = handleAddToQuote;
    
    console.log('Add to Quote script initialized');
})();

// Handle remove from quote
(function() {
    'use strict';
    
    function handleRemoveFromQuote(e) {
        const target = e.target || e.srcElement;
        let link = null;
        
        if (target.closest) {
            link = target.closest('[data-bb-toggle="remove-from-quote"]') || 
                   target.closest('.remove-quote-item');
        }
        
        if (!link) {
            return;
        }
        
        e.preventDefault();
        e.stopPropagation();
        
        if (!confirm('Are you sure you want to remove this item from quote?')) {
            return;
        }
        
        const url = link.getAttribute('data-url') || link.href;
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                if (typeof window.Botble !== 'undefined' && window.Botble.showNotice) {
                    window.Botble.showNotice('error', data.message);
                } else {
                    alert(data.message);
                }
            } else {
                // Update UI
                if (data.data && data.data.count !== undefined) {
                    const quoteCountElements = document.querySelectorAll('.btn-quote span i, .btn-quote span');
                    quoteCountElements.forEach(el => {
                        if (el.tagName === 'I') {
                            el.textContent = data.data.count;
                        } else if (el.querySelector('i')) {
                            el.querySelector('i').textContent = data.data.count;
                        }
                    });
                    
                    const row = link.closest('tr');
                    if (row) {
                        row.remove();
                    }
                    
                    if (data.data.subtotal) {
                        const subtotalElements = document.querySelectorAll('[data-bb-value="quote-subtotal"]');
                        subtotalElements.forEach(el => {
                            el.textContent = data.data.subtotal;
                        });
                    }
                }
                
                // Update quote dropdown content
                if (data.data && data.data.html) {
                    // Find quote dropdown - it's the .ps-cart--mobile sibling of .btn-quote
                    document.querySelectorAll('.btn-quote').forEach(btn => {
                        const cartMini = btn.closest('.ps-cart--mini');
                        if (cartMini) {
                            const dropdown = cartMini.querySelector('.ps-cart--mobile');
                            if (dropdown) {
                                dropdown.innerHTML = data.data.html;
                                
                                // Reinitialize lazy loading if available
                                if (typeof Theme !== 'undefined' && Theme.lazyLoadInstance) {
                                    Theme.lazyLoadInstance.update();
                                }
                            }
                        }
                    });
                }
                
                if (typeof window.Botble !== 'undefined' && window.Botble.showNotice) {
                    window.Botble.showNotice('success', data.message);
                }
                
                document.dispatchEvent(new CustomEvent('quote:updated', { detail: data }));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error removing item from quote');
        });
    }
    
    function attachRemoveListeners() {
        document.removeEventListener('click', handleRemoveFromQuote, true);
        document.addEventListener('click', handleRemoveFromQuote, true);
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', attachRemoveListeners);
    } else {
        attachRemoveListeners();
    }
})();