/**
 * ApexGuild Enterprise Workflow & RBAC State Machine
 * Enterprise JavaScript Interaction Layer
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Sidebar Navigation Toggle
    const mobileToggle = document.getElementById('mobileMenuToggle');
    const sidebar = document.getElementById('appSidebar');

    if (mobileToggle && sidebar) {
        mobileToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            sidebar.classList.toggle('show-mobile');
        });

        // Close when clicking outside
        document.addEventListener('click', function (e) {
            if (sidebar.classList.contains('show-mobile') && !sidebar.contains(e.target) && e.target !== mobileToggle) {
                sidebar.classList.remove('show-mobile');
            }
        });
    }

    // 2. Dynamic Threshold Watcher for Purchase Orders
    const amountInput = document.getElementById('field_amount');
    if (amountInput) {
        const checkThreshold = function () {
            const val = parseFloat(amountInput.value) || 0;
            let banner = document.getElementById('cfo-threshold-banner');

            if (val > 5000) {
                if (!banner) {
                    banner = document.createElement('div');
                    banner.id = 'cfo-threshold-banner';
                    banner.className = 'alert alert-warning mt-2 p-2 px-3 small d-flex align-items-center gap-2 border-warning';
                    banner.innerHTML = `
                        <i class="bi bi-shield-exclamation fs-5 text-warning"></i>
                        <div>
                            <strong>Mandatory CFO Approval Tier Triggered:</strong> Requisitions exceeding \$5,000.00 USD require formal executive sign-off from Chief Financial Officer Sophia Sterling.
                        </div>
                    `;
                    amountInput.closest('.form-group').appendChild(banner);
                }
            } else if (banner) {
                banner.remove();
            }
        };

        amountInput.addEventListener('input', checkThreshold);
        checkThreshold();
    }

    // 3. Digital Signature Copy to Clipboard
    document.querySelectorAll('.copy-sig-btn').forEach(button => {
        button.addEventListener('click', function () {
            const hash = this.getAttribute('data-hash');
            if (hash) {
                navigator.clipboard.writeText(hash).then(() => {
                    const originalHtml = this.innerHTML;
                    this.innerHTML = '<i class="bi bi-check2"></i> Copied!';
                    this.classList.add('text-success');
                    setTimeout(() => {
                        this.innerHTML = originalHtml;
                        this.classList.remove('text-success');
                    }, 2000);
                }).catch(err => {
                    console.error('Copy failed:', err);
                });
            }
        });
    });

    // 4. Auto-dismiss alerts after 6 seconds
    document.querySelectorAll('.alert-dismissible').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 400);
        }, 6000);
    });

    // 5. Interactive Digital Canvas Signature in Approval Modal (if modal exists)
    const sigCanvas = document.getElementById('signatureCanvas');
    if (sigCanvas) {
        const ctx = sigCanvas.getContext('2d');
        let drawing = false;

        ctx.strokeStyle = '#0f172a';
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';

        function getPos(e) {
            const rect = sigCanvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return {
                x: clientX - rect.left,
                y: clientY - rect.top
            };
        }

        function startDrawing(e) {
            drawing = true;
            const pos = getPos(e);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
            e.preventDefault();
        }

        function draw(e) {
            if (!drawing) return;
            const pos = getPos(e);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
            e.preventDefault();
        }

        function stopDrawing() {
            if (drawing) {
                drawing = false;
                const hiddenInput = document.getElementById('signatureDataInput');
                if (hiddenInput) {
                    hiddenInput.value = sigCanvas.toDataURL();
                }
            }
        }

        sigCanvas.addEventListener('mousedown', startDrawing);
        sigCanvas.addEventListener('mousemove', draw);
        sigCanvas.addEventListener('mouseup', stopDrawing);
        sigCanvas.addEventListener('mouseleave', stopDrawing);

        sigCanvas.addEventListener('touchstart', startDrawing);
        sigCanvas.addEventListener('touchmove', draw);
        sigCanvas.addEventListener('touchend', stopDrawing);

        const clearBtn = document.getElementById('clearSigBtn');
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                ctx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
                const hiddenInput = document.getElementById('signatureDataInput');
                if (hiddenInput) {
                    hiddenInput.value = '';
                }
            });
        }
    }
});

/**
 * Open Approval / Rejection Decision Modal
 */
function openApprovalModal(requestId, decision, title) {
    const modalEl = document.getElementById('decisionModal');
    if (!modalEl) return;

    const modal = new bootstrap.Modal(modalEl);
    document.getElementById('modalRequestId').value = requestId;
    document.getElementById('modalDecision').value = decision;
    document.getElementById('modalTitle').textContent = (decision === 'APPROVE') ? 'Authorize Requisition' : 'Decline Requisition';
    
    const submitBtn = document.getElementById('modalSubmitBtn');
    const commentLabel = document.getElementById('modalCommentLabel');
    const commentInput = document.getElementById('modalComment');
    const signatureCanvasSection = document.getElementById('signatureCanvasContainer');

    if (decision === 'APPROVE') {
        submitBtn.className = 'btn btn-success';
        submitBtn.innerHTML = '<i class="bi bi-shield-check"></i> Sign & Authorize Requisition';
        commentLabel.innerHTML = 'Audit Endorsement / Executive Comments <span class="text-muted small">(Optional)</span>';
        commentInput.placeholder = 'e.g. Budget allocation certified. Vendor pricing verified under contract.';
        if (signatureCanvasSection) signatureCanvasSection.style.display = 'block';
    } else {
        submitBtn.className = 'btn btn-danger';
        submitBtn.innerHTML = '<i class="bi bi-x-circle"></i> Reject Requisition';
        commentLabel.innerHTML = 'Mandatory Rejection Rationale <span class="text-danger">*</span>';
        commentInput.placeholder = 'Please state explicit governance, budgetary, or operational reasons for rejection.';
        commentInput.required = true;
        if (signatureCanvasSection) signatureCanvasSection.style.display = 'none';
    }

    modal.show();
}
