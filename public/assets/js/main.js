document.addEventListener('DOMContentLoaded', () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    document.querySelectorAll('.needs-validation').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        });
    });

    document.querySelectorAll('[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.getAttribute('data-confirm') || 'Continue?';

            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    const sidebar = document.getElementById('appSidebar');
    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const sidebarBackdrop = document.querySelector('[data-sidebar-backdrop]');

    const setSidebarOpen = (open) => {
        sidebar?.classList.toggle('is-open', open);
        sidebarBackdrop?.classList.toggle('is-open', open);
        if (sidebarToggle) {
            sidebarToggle.classList.toggle('is-open', open);
            sidebarToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            sidebarToggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
            sidebarToggle.querySelector('i')?.classList.replace(open ? 'bi-list' : 'bi-x-lg', open ? 'bi-x-lg' : 'bi-list');
        }
    };
    const closeSidebar = () => setSidebarOpen(false);

    if (sidebar && sidebarToggle) {
        sidebarToggle.addEventListener('click', () => setSidebarOpen(!sidebar.classList.contains('is-open')));
    }

    sidebarBackdrop?.addEventListener('click', closeSidebar);
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && sidebar?.classList.contains('is-open')) {
            closeSidebar();
        }
    });

    const publicNavLinks = document.querySelector('[data-public-nav-links]');
    const publicNavToggle = document.querySelector('[data-public-nav-toggle]');
    const publicNavBackdrop = document.querySelector('[data-public-nav-backdrop]');

    const closePublicNav = () => {
        publicNavLinks?.classList.remove('is-open');
        publicNavBackdrop?.classList.remove('is-open');
        publicNavToggle?.setAttribute('aria-expanded', 'false');
    };

    if (publicNavLinks && publicNavToggle) {
        publicNavToggle.addEventListener('click', () => {
            const isOpen = publicNavLinks.classList.toggle('is-open');
            publicNavBackdrop?.classList.toggle('is-open', isOpen);
            publicNavToggle.setAttribute('aria-expanded', String(isOpen));
        });

        publicNavLinks.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', closePublicNav);
        });
    }

    publicNavBackdrop?.addEventListener('click', closePublicNav);

    const saleProduct = document.getElementById('sale_product_id');
    const saleUnitPrice = document.getElementById('unit_price');

    if (saleProduct && saleUnitPrice) {
        saleProduct.addEventListener('change', () => {
            const option = saleProduct.options[saleProduct.selectedIndex];
            const price = option ? option.getAttribute('data-price') : '';

            if (price && !saleUnitPrice.value) {
                saleUnitPrice.value = price;
            }
        });
    }

    renderCharts();
    wireLiveProductSearch();
    wireInvoiceBuilder();
    wireProductCarousel();
    wireHomeProductSlider();
    wireScrollReveal();
    wireAjaxForms(csrfToken);
    wireNotifications(csrfToken);
    autoDismissFlash();
});

function wireHomeProductSlider() {
    const slider = document.querySelector('[data-home-slider]');
    const track = slider?.querySelector('[data-slider-track]');
    const slides = track ? Array.from(track.children) : [];

    if (!slider || !track || slides.length === 0) {
        return;
    }

    const prevBtn = slider.querySelector('[data-slider-prev]');
    const nextBtn = slider.querySelector('[data-slider-next]');
    const dotsWrap = slider.querySelector('[data-slider-dots]');
    const gap = 18;
    let index = 0;
    let autoTimer = null;

    const perView = () => {
        const slideWidth = slides[0].getBoundingClientRect().width;
        if (!slideWidth) {
            return 1;
        }
        return Math.max(1, Math.round((track.getBoundingClientRect().width + gap) / (slideWidth + gap)));
    };

    const maxIndex = () => Math.max(0, slides.length - perView());

    const update = () => {
        const slideWidth = slides[0].getBoundingClientRect().width;
        track.style.transform = `translateX(-${index * (slideWidth + gap)}px)`;

        if (dotsWrap) {
            Array.from(dotsWrap.children).forEach((dot, dotIndex) => {
                dot.classList.toggle('is-active', dotIndex === index);
            });
        }
    };

    const renderControls = () => {
        const hasOverflow = maxIndex() > 0;

        if (prevBtn) {
            prevBtn.hidden = !hasOverflow;
        }

        if (nextBtn) {
            nextBtn.hidden = !hasOverflow;
        }

        if (dotsWrap) {
            dotsWrap.hidden = !hasOverflow;
            dotsWrap.innerHTML = '';

            for (let dotIndex = 0; dotIndex <= maxIndex(); dotIndex++) {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.setAttribute('aria-label', `Go to slide ${dotIndex + 1}`);
                dot.addEventListener('click', () => {
                    goTo(dotIndex);
                    restartAuto();
                });
                dotsWrap.appendChild(dot);
            }
        }

        update();
    };

    const goTo = (target) => {
        index = Math.min(Math.max(target, 0), maxIndex());
        update();
    };

    const next = () => {
        goTo(index >= maxIndex() ? 0 : index + 1);
    };

    const prev = () => {
        goTo(index <= 0 ? maxIndex() : index - 1);
    };

    const stopAuto = () => {
        if (autoTimer) {
            window.clearInterval(autoTimer);
            autoTimer = null;
        }
    };

    const startAuto = () => {
        stopAuto();

        if (maxIndex() > 0) {
            autoTimer = window.setInterval(next, 4000);
        }
    };

    const restartAuto = () => {
        stopAuto();
        startAuto();
    };

    nextBtn?.addEventListener('click', () => {
        next();
        restartAuto();
    });

    prevBtn?.addEventListener('click', () => {
        prev();
        restartAuto();
    });

    slider.addEventListener('mouseenter', stopAuto);
    slider.addEventListener('mouseleave', startAuto);

    let resizeTimer = null;
    window.addEventListener('resize', () => {
        window.clearTimeout(resizeTimer);
        resizeTimer = window.setTimeout(() => {
            index = Math.min(index, maxIndex());
            renderControls();
        }, 150);
    });

    renderControls();
    startAuto();
}

function wireScrollReveal() {
    const revealItems = document.querySelectorAll('[data-reveal], [data-reveal-stagger]');

    if (revealItems.length === 0) {
        return;
    }

    if (!('IntersectionObserver' in window)) {
        revealItems.forEach((item) => item.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                obs.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });

    revealItems.forEach((item) => observer.observe(item));
}

const doughnutCenterTextPlugin = {
    id: 'doughnutCenterText',
    afterDraw(chart) {
        if (chart.config.type !== 'doughnut') {
            return;
        }

        const dataset = chart.data.datasets[0];
        const values = (dataset && dataset.data) || [];
        const total = values.reduce((sum, value) => sum + Number(value || 0), 0);
        const { ctx, chartArea } = chart;

        if (!chartArea) {
            return;
        }

        const cx = (chartArea.left + chartArea.right) / 2;
        const cy = (chartArea.top + chartArea.bottom) / 2;

        ctx.save();
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        if (total > 0) {
            let topIndex = 0;
            values.forEach((value, index) => {
                if (Number(value) > Number(values[topIndex])) {
                    topIndex = index;
                }
            });

            const topLabel = (chart.data.labels && chart.data.labels[topIndex]) || '';
            const percentage = Math.round((Number(values[topIndex]) / total) * 100);

            ctx.fillStyle = '#101828';
            ctx.font = '700 22px Inter, system-ui, sans-serif';
            ctx.fillText(String(total), cx, cy - 12);

            ctx.fillStyle = '#667085';
            ctx.font = '600 12px Inter, system-ui, sans-serif';
            ctx.fillText(`${topLabel} · ${percentage}%`, cx, cy + 12);
        } else {
            ctx.fillStyle = '#98a2b3';
            ctx.font = '600 13px Inter, system-ui, sans-serif';
            ctx.fillText('No data yet', cx, cy);
        }

        ctx.restore();
    },
};

function renderCharts() {
    if (!window.Chart) {
        return;
    }

    if (!window.__dcfChartPluginsRegistered) {
        Chart.register(doughnutCenterTextPlugin);
        window.__dcfChartPluginsRegistered = true;
    }

    document.querySelectorAll('[data-chart]').forEach((canvas) => {
        let config;

        try {
            config = JSON.parse(canvas.getAttribute('data-chart'));
        } catch {
            return;
        }

        const isDoughnut = config.type === 'doughnut';

        new Chart(canvas, {
            type: config.type || 'bar',
            data: {
                labels: config.labels || [],
                datasets: [{
                    label: config.label || 'Total',
                    data: config.data || [],
                    backgroundColor: config.backgroundColor || '#0f766e',
                    borderColor: config.borderColor || '#115e59',
                    borderWidth: isDoughnut ? 0 : 1,
                    hoverOffset: isDoughnut ? 6 : 0,
                    tension: 0.35,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: isDoughnut ? '70%' : undefined,
                plugins: {
                    legend: {
                        display: Boolean(config.showLegend),
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 14,
                            font: { size: 11.5, weight: '600' },
                            color: '#475569',
                        },
                    },
                    tooltip: {
                        backgroundColor: '#101828',
                        titleFont: { size: 12, weight: '700' },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: true,
                        boxPadding: 4,
                    },
                },
                scales: isDoughnut ? {} : {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                        },
                        grid: {
                            color: '#f0f2f5',
                        },
                    },
                    x: {
                        grid: {
                            display: false,
                        },
                    },
                },
            },
        });
    });
}

function wireLiveProductSearch() {
    const form = document.querySelector('[data-live-product-search]');
    const tableBody = document.getElementById('productsTableBody');

    if (!form || !tableBody) {
        return;
    }

    let timer = null;

    const runSearch = () => {
        const url = new URL(form.getAttribute('data-live-product-search'), window.location.origin);
        new FormData(form).forEach((value, key) => {
            if (value !== '') {
                url.searchParams.set(key, value);
            }
        });

        fetch(url.toString(), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => response.json())
            .then((payload) => {
                if (!payload.success) {
                    return;
                }

                tableBody.innerHTML = payload.products.length
                    ? payload.products.map(productRow).join('')
                    : '<tr><td colspan="7" class="text-center text-muted py-4">No products found.</td></tr>';
            })
            .catch(() => {});
    };

    form.querySelectorAll('input, select').forEach((field) => {
        field.addEventListener('input', () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(runSearch, 250);
        });

        field.addEventListener('change', runSearch);
    });
}

function wireInvoiceBuilder() {
    const form = document.querySelector('[data-invoice-builder]');

    if (!form) {
        return;
    }

    const currency = window.invoiceCurrency || 'TZS';
    const productUrlTemplate = form.getAttribute('data-product-url-template') || '';
    const productSelect = form.querySelector('[data-invoice-product-select]');
    const addProductButton = form.querySelector('[data-add-invoice-product]');
    const itemsBody = form.querySelector('[data-invoice-items]');
    const template = document.getElementById('invoiceItemTemplate');
    const customerSelect = form.querySelector('[data-customer-select]');
    const existingCustomer = form.querySelector('[data-existing-customer]');
    const newCustomer = form.querySelector('[data-new-customer]');
    const customerModeFields = form.querySelectorAll('input[name="customer_mode"]');

    const formatCurrency = (amount) => {
        const decimals = ['TZS', 'UGX', 'RWF'].includes(String(currency).toUpperCase()) ? 0 : 2;

        return `${currency} ${Number(amount || 0).toLocaleString(undefined, {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        })}`;
    };

    const publicImageUrl = (path) => {
        if (!path) {
            return '';
        }

        if (/^https?:\/\//i.test(path)) {
            return path;
        }

        const base = window.location.pathname.replace(/\/index\.php.*$/, '').replace(/\/$/, '');
        return `${window.location.origin}${base}/${String(path).replace(/^\/+/, '')}`;
    };

    const fillCustomerFields = () => {
        const option = customerSelect?.options[customerSelect.selectedIndex];

        if (!option) {
            return;
        }

        form.querySelector('[data-customer-field="name"]')?.setAttribute('value', option.getAttribute('data-name') || '');
        form.querySelector('[data-customer-field="company"]')?.setAttribute('value', option.getAttribute('data-company') || '');
        form.querySelector('[data-customer-field="phone"]')?.setAttribute('value', option.getAttribute('data-phone') || '');
        form.querySelector('[data-customer-field="email"]')?.setAttribute('value', option.getAttribute('data-email') || '');
        form.querySelector('[data-customer-field="address"]')?.setAttribute('value', option.getAttribute('data-address') || '');
        form.querySelector('[data-customer-field="tin"]')?.setAttribute('value', option.getAttribute('data-tin') || '');
        form.querySelector('[data-customer-field="vrn"]')?.setAttribute('value', option.getAttribute('data-vrn') || '');

        form.querySelector('[data-customer-field="name"]') && (form.querySelector('[data-customer-field="name"]').value = option.getAttribute('data-name') || '');
        form.querySelector('[data-customer-field="company"]') && (form.querySelector('[data-customer-field="company"]').value = option.getAttribute('data-company') || '');
        form.querySelector('[data-customer-field="phone"]') && (form.querySelector('[data-customer-field="phone"]').value = option.getAttribute('data-phone') || '');
        form.querySelector('[data-customer-field="email"]') && (form.querySelector('[data-customer-field="email"]').value = option.getAttribute('data-email') || '');
        form.querySelector('[data-customer-field="address"]') && (form.querySelector('[data-customer-field="address"]').value = option.getAttribute('data-address') || '');
        form.querySelector('[data-customer-field="tin"]') && (form.querySelector('[data-customer-field="tin"]').value = option.getAttribute('data-tin') || '');
        form.querySelector('[data-customer-field="vrn"]') && (form.querySelector('[data-customer-field="vrn"]').value = option.getAttribute('data-vrn') || '');
    };

    const syncCustomerMode = () => {
        const mode = form.querySelector('input[name="customer_mode"]:checked')?.value || 'existing';
        const usingExisting = mode === 'existing';

        if (existingCustomer) {
            existingCustomer.hidden = !usingExisting;
        }

        if (newCustomer) {
            newCustomer.hidden = usingExisting && existingCustomer;
        }

        newCustomer?.querySelectorAll('input').forEach((field) => {
            field.required = (!usingExisting || !existingCustomer) && ['customer_name', 'phone', 'email', 'address'].includes(field.id);
        });

        if (usingExisting && customerSelect) {
            fillCustomerFields();
        }
    };

    const calculateRow = (row) => {
        const quantity = Number(row.querySelector('[data-field-quantity]').value || 0);
        const unitPrice = Number(row.querySelector('[data-field-unit-price]').value || 0);
        const discount = Number(row.querySelector('[data-field-line-discount]').value || 0);
        const amount = Math.max((quantity * unitPrice) - discount, 0);
        row.querySelector('[data-field-amount-display]').value = formatCurrency(amount);
        return amount;
    };

    const calculateTotals = () => {
        const subtotal = [...itemsBody.querySelectorAll('[data-invoice-item-row]')]
            .reduce((sum, row) => sum + calculateRow(row), 0);
        const invoiceDiscount = Math.min(Number(form.querySelector('[data-invoice-discount]').value || 0), subtotal);
        const vatRate = Number(form.querySelector('[data-vat-rate]').value || 0);
        const taxable = Math.max(subtotal - invoiceDiscount, 0);
        const vat = taxable * Math.max(vatRate, 0) / 100;
        const grand = taxable + vat;
        const status = form.querySelector('#status')?.value || 'draft';
        const balance = status === 'paid' ? 0 : grand;

        form.querySelector('[data-total-subtotal]').textContent = formatCurrency(subtotal);
        form.querySelector('[data-total-discount]').textContent = formatCurrency(invoiceDiscount);
        form.querySelector('[data-total-vat]').textContent = formatCurrency(vat);
        form.querySelector('[data-total-grand]').textContent = formatCurrency(grand);
        form.querySelector('[data-total-balance]').textContent = formatCurrency(balance);
    };

    const wireRow = (row) => {
        row.querySelectorAll('input, textarea').forEach((field) => {
            field.addEventListener('input', calculateTotals);
            field.addEventListener('change', calculateTotals);
        });

        row.querySelector('[data-remove-invoice-item]').addEventListener('click', () => {
            row.remove();
            calculateTotals();
        });
    };

    const addItem = (product) => {
        const fragment = template.content.cloneNode(true);
        const row = fragment.querySelector('[data-invoice-item-row]');
        const image = row.querySelector('[data-item-image]');
        const placeholder = row.querySelector('[data-item-placeholder]');

        row.querySelector('[data-field-product-id]').value = product.id || '';
        row.querySelector('[data-field-product-image]').value = product.image || '';
        row.querySelector('[data-field-product-name]').value = product.name || '';
        row.querySelector('[data-field-description]').value = product.description || '';
        row.querySelector('[data-field-specifications]').value = product.specifications || '';
        row.querySelector('[data-field-available]').textContent = product.quantity ?? 0;
        row.querySelector('[data-field-quantity]').max = product.quantity || '';
        row.querySelector('[data-field-unit-price]').value = product.price || 0;

        if (product.image) {
            image.src = publicImageUrl(product.image);
            image.hidden = false;
            placeholder.hidden = true;
        } else {
            image.hidden = true;
            placeholder.hidden = false;
        }

        wireRow(row);
        itemsBody.appendChild(fragment);
        calculateTotals();
    };

    const fetchProduct = (explicitProductId) => {
        const productId = explicitProductId || productSelect?.value;

        if (!productId || !productUrlTemplate) {
            return;
        }

        if (addProductButton) {
            addProductButton.disabled = true;
        }

        fetch(productUrlTemplate.replace('__ID__', productId), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => response.json())
            .then((payload) => {
                if (payload.success) {
                    addItem(payload.product);

                    if (productSelect) {
                        productSelect.value = '';
                    }
                }
            })
            .catch(() => {})
            .finally(() => {
                if (addProductButton) {
                    addProductButton.disabled = false;
                }
            });
    };

    addProductButton?.addEventListener('click', () => fetchProduct());
    customerSelect?.addEventListener('change', fillCustomerFields);
    customerModeFields.forEach((field) => field.addEventListener('change', syncCustomerMode));
    form.querySelector('[data-invoice-discount]')?.addEventListener('input', calculateTotals);
    form.querySelector('[data-vat-rate]')?.addEventListener('input', calculateTotals);
    form.querySelector('#status')?.addEventListener('change', calculateTotals);

    form.addEventListener('submit', (event) => {
        const mode = form.querySelector('input[name="customer_mode"]:checked')?.value || 'existing';

        if (mode === 'existing' && customerSelect && !customerSelect.value) {
            event.preventDefault();
            customerSelect.focus();
            customerSelect.classList.add('is-invalid');
            return;
        }

        if (!itemsBody.querySelector('[data-invoice-item-row]')) {
            event.preventDefault();
            alert('Add at least one invoice item.');
        }
    });

    syncCustomerMode();
    calculateTotals();

    const preselectedProductId = form.getAttribute('data-preselected-product');

    if (preselectedProductId) {
        fetchProduct(preselectedProductId);
    }
}

function productRow(product) {
    const statusClass = product.status === 'active' ? 'text-bg-success' : 'text-bg-danger';
    const stockClass = product.quantity <= 5 ? 'text-bg-warning' : 'text-bg-light text-dark';

    return `
        <tr>
            <td>
                <div class="product-cell">
                    <span class="product-placeholder"><i class="bi bi-laptop"></i></span>
                    <div>
                        <strong>${escapeHtml(product.name)}</strong>
                        <small class="code-text">${escapeHtml(product.sku || '')}</small>
                    </div>
                </div>
            </td>
            <td>${escapeHtml(product.category)}</td>
            <td>${escapeHtml(product.brand)}</td>
            <td>${formatProductMoney(product.price)}</td>
            <td><span class="badge ${stockClass}">${product.quantity}</span></td>
            <td><span class="badge ${statusClass}">${escapeHtml(readableStatus(product.status))}</span></td>
            <td class="text-end text-muted">Live result</td>
        </tr>
    `;
}

function wireProductCarousel() {
    document.querySelectorAll('[data-product-carousel]').forEach((carousel) => {
        const images = Array.from(carousel.querySelectorAll('.product-carousel-image'));
        const prev = carousel.querySelector('.product-carousel-prev');
        const next = carousel.querySelector('.product-carousel-next');
        const thumbs = Array.from(document.querySelectorAll('.product-thumb'));

        if (!images.length) {
            return;
        }

        let activeIndex = 0;

        const update = (index) => {
            activeIndex = (index + images.length) % images.length;
            images.forEach((img, imgIndex) => img.classList.toggle('active', imgIndex === activeIndex));
            thumbs.forEach((thumb, thumbIndex) => thumb.classList.toggle('active', thumbIndex === activeIndex));
        };

        prev?.addEventListener('click', () => update(activeIndex - 1));
        next?.addEventListener('click', () => update(activeIndex + 1));
        thumbs.forEach((thumb) => {
            thumb.addEventListener('click', () => update(Number(thumb.getAttribute('data-thumb-index') || 0)));
        });

        let touchStartX = 0;
        carousel.addEventListener('touchstart', (event) => {
            touchStartX = event.touches[0]?.clientX || 0;
        }, { passive: true });
        carousel.addEventListener('touchend', (event) => {
            const touchEndX = event.changedTouches[0]?.clientX || 0;
            const diff = touchStartX - touchEndX;
            if (Math.abs(diff) > 40) {
                update(activeIndex + (diff > 0 ? 1 : -1));
            }
        }, { passive: true });
    });
}

function wireAjaxForms(csrfToken) {
    document.querySelectorAll('[data-ajax-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (event.defaultPrevented) {
                return;
            }

            event.preventDefault();

            const button = form.querySelector('button[type="submit"]');
            const originalHtml = button ? button.innerHTML : '';
            const formData = new FormData(form);

            if (csrfToken && !formData.has('csrf_token')) {
                formData.append('csrf_token', csrfToken);
            }

            if (button) {
                button.disabled = true;
                button.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            }

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then((response) => response.json())
                .then((payload) => {
                    showAjaxMessage(form, payload.message || 'Action completed.', payload.success);

                    if (payload.success) {
                        updateStatusBadge(form, payload);
                    }
                })
                .catch(() => {
                    showAjaxMessage(form, 'Network error. Please try again.', false);
                })
                .finally(() => {
                    if (button) {
                        button.disabled = false;
                        button.innerHTML = originalHtml;
                    }
                });
        });
    });
}

function updateStatusBadge(form, payload) {
    const targetSelector = form.getAttribute('data-status-target');
    const target = targetSelector ? document.querySelector(targetSelector) : null;

    if (!target || !payload.status_label || !payload.badge_class) {
        return;
    }

    target.className = `badge ${payload.badge_class}`;
    target.textContent = payload.status_label;
}

function showAjaxMessage(form, message, success) {
    const messageBox = form.closest('td, .panel, tr')?.querySelector('.ajax-message') || form.querySelector('.ajax-message');

    if (!messageBox) {
        return;
    }

    messageBox.className = `ajax-message is-visible small ${success ? 'text-success' : 'text-danger'}`;
    messageBox.textContent = message;
}

function wireNotifications(csrfToken) {
    document.querySelectorAll('[data-notification-read]').forEach((button) => {
        button.addEventListener('click', () => {
            const notificationId = button.getAttribute('data-notification-read');
            const formData = new FormData();
            formData.append('notification_id', notificationId);
            formData.append('csrf_token', csrfToken);

            fetch(button.getAttribute('data-url'), {
                method: 'POST',
                body: formData,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
                .then((response) => response.json())
                .then((payload) => {
                    if (payload.success) {
                        button.closest('.notification-item')?.classList.add('is-read');
                        button.remove();
                    }
                })
                .catch(() => {});
        });
    });
}

function autoDismissFlash() {
    window.setTimeout(() => {
        document.querySelectorAll('.flash-stack .alert').forEach((alert) => {
            alert.style.transition = 'opacity 180ms ease';
            alert.style.opacity = '0';
            window.setTimeout(() => alert.remove(), 220);
        });
    }, 4500);
}

function readableStatus(status) {
    return String(status || '').replaceAll('_', ' ').replace(/\b\w/g, (char) => char.toUpperCase());
}

function formatProductMoney(amount) {
    return `TZS ${Number(amount || 0).toLocaleString(undefined, {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    })}`;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-caption-toggle]');
    if (!toggle) {
        return;
    }

    const caption = document.getElementById(toggle.dataset.captionToggle);
    const expanded = caption?.classList.toggle('is-expanded') ?? false;
    toggle.textContent = expanded ? 'Show less' : 'Read more';
    toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
});

/*
 * Barcode scanning on the Sales Tracking forms. USB/Bluetooth scanners type the code and press
 * Enter, so the scan box looks the code up among the product options (by SKU) and selects it.
 * Phones with a camera and the BarcodeDetector API (Chrome on Android) get a camera button too.
 */
document.querySelectorAll('[data-barcode-scan]').forEach((wrapper) => {
    const select = document.getElementById(wrapper.dataset.barcodeScan);
    const input = wrapper.querySelector('[data-barcode-input]');
    const feedback = wrapper.parentElement.querySelector('[data-barcode-feedback]');
    const cameraButton = wrapper.querySelector('[data-barcode-camera]');
    if (!select || !input) {
        return;
    }

    const say = (text, ok) => {
        if (feedback) {
            feedback.textContent = text;
            feedback.className = 'barcode-feedback ' + (ok ? 'text-success' : 'text-danger');
        }
    };

    const pick = (code) => {
        const wanted = code.trim().toLowerCase();
        if (!wanted) {
            return;
        }
        const option = Array.from(select.options).find((o) => (o.dataset.sku || '').toLowerCase() === wanted);
        if (option) {
            select.value = option.value;
            select.dispatchEvent(new Event('change', { bubbles: true }));
            say('Selected: ' + option.textContent.trim(), true);
            input.value = '';
        } else {
            say('No product with code "' + code.trim() + '"', false);
            input.select();
        }
    };

    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault(); // a scanner's Enter must not submit the form
            pick(input.value);
        }
    });

    if (cameraButton && 'BarcodeDetector' in window && navigator.mediaDevices?.getUserMedia) {
        cameraButton.hidden = false;
        let stream = null;
        let video = null;

        const stop = () => {
            stream?.getTracks().forEach((track) => track.stop());
            video?.remove();
            stream = null;
            video = null;
        };

        cameraButton.addEventListener('click', async () => {
            if (stream) {
                stop();
                return;
            }
            try {
                const detector = new window.BarcodeDetector();
                stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
                video = document.createElement('video');
                video.className = 'barcode-video';
                video.setAttribute('playsinline', '');
                video.srcObject = stream;
                wrapper.after(video);
                await video.play();
                say('Point the camera at the barcode…', true);

                const scan = async () => {
                    if (!stream) {
                        return;
                    }
                    const codes = await detector.detect(video).catch(() => []);
                    if (codes.length) {
                        pick(codes[0].rawValue);
                        stop();
                        return;
                    }
                    requestAnimationFrame(scan);
                };
                scan();
            } catch (error) {
                stop();
                say('Camera not available: ' + error.message, false);
            }
        });
    }
});

/*
 * Product form: specifications table (add/remove rows, category templates) and the
 * "Save X%" preview for the price-before-discount field.
 */
document.querySelectorAll('[data-spec-editor]').forEach((editor) => {
    const rowsBox = editor.querySelector('[data-spec-rows]');
    const form = editor.closest('form');
    const presets = {
        laptop: ['Processor', 'RAM', 'Storage', 'Display', 'Graphics', 'Operating system', 'Battery', 'Weight', 'Ports', 'Keyboard'],
        desktop: ['Processor', 'RAM', 'Storage', 'Graphics', 'Operating system', 'Ports', 'Form factor', 'In the box'],
        monitor: ['Screen size', 'Resolution', 'Panel type', 'Refresh rate', 'Response time', 'Ports', 'Stand'],
        printer: ['Print type', 'Functions', 'Print speed', 'Print resolution', 'Connectivity', 'Paper size', 'Cartridge'],
        accessory: ['Type', 'Connectivity', 'Compatibility', 'Colour', 'In the box'],
    };

    const makeRow = (label = '', value = '') => {
        const template = rowsBox.querySelector('[data-spec-row]');
        const row = template.cloneNode(true);
        const [labelInput, valueInput] = row.querySelectorAll('input');
        labelInput.value = label;
        valueInput.value = value;
        return row;
    };

    editor.querySelector('[data-spec-add]')?.addEventListener('click', () => {
        const row = makeRow();
        rowsBox.appendChild(row);
        row.querySelector('input').focus();
    });

    rowsBox.addEventListener('click', (event) => {
        const remove = event.target.closest('[data-spec-remove]');
        if (!remove) {
            return;
        }
        const rows = rowsBox.querySelectorAll('[data-spec-row]');
        const row = remove.closest('[data-spec-row]');
        if (rows.length > 1) {
            row.remove();
        } else {
            row.querySelectorAll('input').forEach((input) => { input.value = ''; });
        }
    });

    form?.querySelectorAll('[data-spec-preset]').forEach((button) => {
        button.addEventListener('click', () => {
            const labels = presets[button.dataset.specPreset] || [];
            // Keep whatever details were already typed, matched by feature name.
            const existing = Array.from(rowsBox.querySelectorAll('[data-spec-row]')).map((row) => {
                const [labelInput, valueInput] = row.querySelectorAll('input');
                return { label: labelInput.value.trim(), value: valueInput.value.trim() };
            }).filter((row) => row.label || row.value);
            const byLabel = new Map(existing.filter((row) => row.label).map((row) => [row.label.toLowerCase(), row.value]));
            const template = rowsBox.querySelector('[data-spec-row]');

            const rows = labels.map((label) => makeRow(label, byLabel.get(label.toLowerCase()) || ''));
            existing
                .filter((row) => !labels.some((label) => label.toLowerCase() === row.label.toLowerCase()))
                .forEach((row) => rows.push(makeRow(row.label, row.value)));

            rowsBox.replaceChildren(...rows.length ? rows : [template]);
            rowsBox.querySelector('[data-spec-row] input:nth-of-type(2)')?.focus();
        });
    });
});

document.querySelectorAll('[data-compare-input]').forEach((compareInput) => {
    const form = compareInput.closest('form');
    const priceInput = form?.querySelector('[data-price-input]');
    const preview = form?.querySelector('[data-discount-preview]');
    if (!priceInput || !preview) {
        return;
    }
    const update = () => {
        const price = parseFloat(priceInput.value) || 0;
        const was = parseFloat(compareInput.value) || 0;
        preview.textContent = was > price && was > 0
            ? 'Customers see: Save ' + Math.round((was - price) / was * 100) + '%'
            : 'Shown crossed out when higher than the selling price.';
        preview.classList.toggle('text-success', was > price && was > 0);
    };
    priceInput.addEventListener('input', update);
    compareInput.addEventListener('input', update);
});
