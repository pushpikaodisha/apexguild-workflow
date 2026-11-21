<?php
/**
 * Visual Workflow Pipeline Builder (Admin Only)
 */
?>

<div class="mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <h2 class="fw-bold mb-1" style="letter-spacing: -0.02em;">Visual Workflow & Step Builder</h2>
            <p class="text-muted small mb-0">Define state machine approval pipelines, conditional routing rules, and dynamic form schema.</p>
        </div>
        <a href="/workflows" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Templates
        </a>
    </div>
</div>

<form action="/workflows/create" method="POST" id="workflowBuilderForm">
    <input type="hidden" name="_csrf" value="<?= $csrfToken ?>">
    <input type="hidden" name="schema_json" id="schemaJsonInput" value="">
    <input type="hidden" name="steps_json" id="stepsJsonInput" value="">

    <div class="row g-4">
        <!-- Workflow Metadata -->
        <div class="col-12 col-lg-5">
            <div class="enterprise-card h-100">
                <div class="card-header-custom">
                    <h3 class="card-title-custom">
                        <i class="bi bi-info-circle text-primary"></i>
                        <span>Workflow Specification</span>
                    </h3>
                </div>
                <div class="card-body-custom">
                    <div class="mb-3">
                        <label for="wfTitle" class="form-label small fw-semibold text-secondary text-uppercase tracking-wider">Workflow Title <span class="text-danger">*</span></label>
                        <input type="text" id="wfTitle" name="title" class="form-control enterprise-input" placeholder="e.g. Capital Asset Acquisition" required>
                    </div>

                    <div class="mb-3">
                        <label for="wfCode" class="form-label small fw-semibold text-secondary text-uppercase tracking-wider">Unique Template Code <span class="text-danger">*</span></label>
                        <input type="text" id="wfCode" name="code" class="form-control enterprise-input font-monospace" placeholder="e.g. CAPEX_ACQUISITION" required style="text-transform: uppercase;">
                        <div class="form-text small">Alpha-numeric uppercase with underscores.</div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label for="wfCategory" class="form-label small fw-semibold text-secondary text-uppercase tracking-wider">Category</label>
                            <select id="wfCategory" name="category" class="form-select enterprise-input">
                                <option value="Finance & Procurement">Finance & Procurement</option>
                                <option value="Human Capital">Human Capital</option>
                                <option value="Security & Compliance">Security & Compliance</option>
                                <option value="Engineering Operations">Engineering Operations</option>
                                <option value="Legal & Governance">Legal & Governance</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label for="wfIcon" class="form-label small fw-semibold text-secondary text-uppercase tracking-wider">Bootstrap Icon</label>
                            <input type="text" id="wfIcon" name="icon" class="form-control enterprise-input" value="bi-diagram-3" placeholder="bi-diagram-3">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="wfDesc" class="form-label small fw-semibold text-secondary text-uppercase tracking-wider">Operational Description <span class="text-danger">*</span></label>
                        <textarea id="wfDesc" name="description" class="form-control enterprise-input" rows="4" placeholder="Explain the business objective, scope, and governance policies driving this workflow..." required></textarea>
                    </div>

                    <div class="p-3 bg-light rounded-3 border small">
                        <strong class="text-dark"><i class="bi bi-shield-check text-success me-1"></i> State Machine Engine:</strong>
                        <p class="text-muted mb-0 mt-1">Transitions adhere to deterministic multi-tier logic. When a request is submitted, it routes sequentially through the active steps defined on the right.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approval Steps & Dynamic Schema -->
        <div class="col-12 col-lg-7">
            <!-- Approval Pipeline Steps -->
            <div class="enterprise-card mb-4">
                <div class="card-header-custom">
                    <h3 class="card-title-custom">
                        <i class="bi bi-diagram-2 text-warning"></i>
                        <span>Approval Tier Pipeline (Ordered Steps)</span>
                    </h3>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addStepBtn">
                        <i class="bi bi-plus-circle"></i> Add Step
                    </button>
                </div>
                <div class="card-body-custom">
                    <div id="stepsContainer" class="d-flex flex-column gap-3">
                        <!-- Step items will be populated by JS -->
                    </div>
                </div>
            </div>

            <!-- Dynamic Form Schema Builder -->
            <div class="enterprise-card mb-4">
                <div class="card-header-custom">
                    <h3 class="card-title-custom">
                        <i class="bi bi-ui-checks text-success"></i>
                        <span>Dynamic Form Schema (Fields)</span>
                    </h3>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addFieldBtn">
                        <i class="bi bi-plus-circle"></i> Add Field
                    </button>
                </div>
                <div class="card-body-custom">
                    <div id="fieldsContainer" class="d-flex flex-column gap-3">
                        <!-- Field items will be populated by JS -->
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 mb-4">
                <a href="/workflows" class="btn btn-outline-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4" id="savePipelineBtn">
                    <i class="bi bi-check2-circle"></i> Publish Workflow Pipeline
                </button>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Initial sample steps
    const defaultSteps = [
        { step_number: 1, step_name: 'Operations Manager Review', approver_role: 'Manager', condition: 'always', sla_hours: 24, description: 'Verify operational budget & need.' },
        { step_number: 2, step_name: 'CFO Fiscal Clearance', approver_role: 'CFO', condition: 'amount_gt_5000', sla_hours: 48, description: 'Mandatory fiscal sign-off if amount > $5,000.' },
        { step_number: 3, step_name: 'Admin ERP Execution', approver_role: 'Admin', condition: 'always', sla_hours: 12, description: 'Final contract binding & system dispatch.' }
    ];

    // Initial sample fields
    const defaultFields = [
        { name: 'requisition_item', label: 'Item / Project Name', type: 'text', required: true, col: 12, placeholder: 'Enter specification' },
        { name: 'vendor', label: 'Preferred Vendor', type: 'text', required: true, col: 6, placeholder: 'Vendor company' },
        { name: 'amount', label: 'Estimated Amount ($ USD)', type: 'amount', required: true, col: 6, placeholder: '0.00' },
        { name: 'justification', label: 'Business Justification', type: 'textarea', required: true, col: 12, placeholder: 'Reason for request...' }
    ];

    let steps = [...defaultSteps];
    let fields = [...defaultFields];

    const stepsContainer = document.getElementById('stepsContainer');
    const fieldsContainer = document.getElementById('fieldsContainer');

    function renderSteps() {
        stepsContainer.innerHTML = '';
        steps.forEach((step, index) => {
            const el = document.createElement('div');
            el.className = 'p-3 border rounded-3 bg-light-subtle';
            el.innerHTML = `
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-primary">Step ${index + 1}</span>
                    <button type="button" class="btn btn-link text-danger p-0 delete-step-btn" data-index="${index}" ${steps.length <= 1 ? 'disabled' : ''}>
                        <i class="bi bi-trash"></i> Remove
                    </button>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Step Name</label>
                        <input type="text" class="form-control form-control-sm step-name" data-index="${index}" value="${step.step_name}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-secondary">Approver Role</label>
                        <select class="form-select form-select-sm step-role" data-index="${index}">
                            <option value="Manager" ${step.approver_role === 'Manager' ? 'selected' : ''}>Manager</option>
                            <option value="CFO" ${step.approver_role === 'CFO' ? 'selected' : ''}>CFO</option>
                            <option value="Admin" ${step.approver_role === 'Admin' ? 'selected' : ''}>Admin</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-secondary">SLA (Hours)</label>
                        <input type="number" class="form-control form-control-sm step-sla" data-index="${index}" value="${step.sla_hours || 24}" min="1">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold text-secondary">Routing Condition</label>
                        <select class="form-select form-select-sm step-condition" data-index="${index}">
                            <option value="always" ${step.condition === 'always' ? 'selected' : ''}>Always Required</option>
                            <option value="amount_gt_5000" ${step.condition === 'amount_gt_5000' ? 'selected' : ''}>Only if Amount > $5,000 (CFO Rule)</option>
                        </select>
                    </div>
                </div>
            `;
            stepsContainer.appendChild(el);
        });

        // Re-attach listeners
        document.querySelectorAll('.delete-step-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const idx = parseInt(this.getAttribute('data-index'), 10);
                if (steps.length > 1) {
                    steps.splice(idx, 1);
                    steps.forEach((s, i) => s.step_number = i + 1);
                    renderSteps();
                }
            });
        });

        document.querySelectorAll('.step-name').forEach(input => {
            input.addEventListener('input', function () {
                steps[this.getAttribute('data-index')].step_name = this.value;
            });
        });

        document.querySelectorAll('.step-role').forEach(sel => {
            sel.addEventListener('change', function () {
                steps[this.getAttribute('data-index')].approver_role = this.value;
            });
        });

        document.querySelectorAll('.step-sla').forEach(input => {
            input.addEventListener('input', function () {
                steps[this.getAttribute('data-index')].sla_hours = parseInt(this.value, 10) || 24;
            });
        });

        document.querySelectorAll('.step-condition').forEach(sel => {
            sel.addEventListener('change', function () {
                steps[this.getAttribute('data-index')].condition = this.value;
            });
        });
    }

    function renderFields() {
        fieldsContainer.innerHTML = '';
        fields.forEach((field, index) => {
            const el = document.createElement('div');
            el.className = 'p-3 border rounded-3 bg-light-subtle';
            el.innerHTML = `
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-secondary">Field ${index + 1}</span>
                    <button type="button" class="btn btn-link text-danger p-0 delete-field-btn" data-index="${index}" ${fields.length <= 1 ? 'disabled' : ''}>
                        <i class="bi bi-trash"></i> Remove
                    </button>
                </div>
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Field Key</label>
                        <input type="text" class="form-control form-control-sm field-name" data-index="${index}" value="${field.name}" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold text-secondary">Field Label</label>
                        <input type="text" class="form-control form-control-sm field-label" data-index="${index}" value="${field.label}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-secondary">Type</label>
                        <select class="form-select form-select-sm field-type" data-index="${index}">
                            <option value="text" ${field.type === 'text' ? 'selected' : ''}>Text</option>
                            <option value="textarea" ${field.type === 'textarea' ? 'selected' : ''}>Textarea</option>
                            <option value="amount" ${field.type === 'amount' ? 'selected' : ''}>Amount ($)</option>
                            <option value="number" ${field.type === 'number' ? 'selected' : ''}>Number</option>
                            <option value="date" ${field.type === 'date' ? 'selected' : ''}>Date</option>
                            <option value="file" ${field.type === 'file' ? 'selected' : ''}>File Attachment</option>
                        </select>
                    </div>
                </div>
            `;
            fieldsContainer.appendChild(el);
        });

        // Re-attach listeners
        document.querySelectorAll('.delete-field-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const idx = parseInt(this.getAttribute('data-index'), 10);
                if (fields.length > 1) {
                    fields.splice(idx, 1);
                    renderFields();
                }
            });
        });

        document.querySelectorAll('.field-name').forEach(input => {
            input.addEventListener('input', function () {
                fields[this.getAttribute('data-index')].name = this.value;
            });
        });

        document.querySelectorAll('.field-label').forEach(input => {
            input.addEventListener('input', function () {
                fields[this.getAttribute('data-index')].label = this.value;
            });
        });

        document.querySelectorAll('.field-type').forEach(sel => {
            sel.addEventListener('change', function () {
                fields[this.getAttribute('data-index')].type = this.value;
            });
        });
    }

    document.getElementById('addStepBtn').addEventListener('click', function () {
        steps.push({
            step_number: steps.length + 1,
            step_name: 'New Approval Tier',
            approver_role: 'Manager',
            condition: 'always',
            sla_hours: 24,
            description: ''
        });
        renderSteps();
    });

    document.getElementById('addFieldBtn').addEventListener('click', function () {
        fields.push({
            name: 'custom_field_' + (fields.length + 1),
            label: 'Custom Attribute ' + (fields.length + 1),
            type: 'text',
            required: false,
            col: 12,
            placeholder: ''
        });
        renderFields();
    });

    document.getElementById('workflowBuilderForm').addEventListener('submit', function (e) {
        document.getElementById('stepsJsonInput').value = JSON.stringify(steps);
        document.getElementById('schemaJsonInput').value = JSON.stringify(fields);
    });

    renderSteps();
    renderFields();
});
</script>
