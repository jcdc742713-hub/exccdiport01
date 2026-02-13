<template>
  <div class="approvals-index">
    <div class="header">
      <h1>My Approvals</h1>
      <span class="pending-count">
        {{ pendingCount }} pending
      </span>
    </div>

    <div class="filters">
      <select v-model="filters.status" @change="search">
        <option value="">All Statuses</option>
        <option value="pending">Pending</option>
        <option value="approved">Approved</option>
        <option value="rejected">Rejected</option>
      </select>
    </div>

    <div class="approvals-list">
      <div
        v-for="approval in approvals.data"
        :key="approval.id"
        class="approval-card"
      >
        <div class="approval-header">
          <div>
            <h3>{{ approval.workflow_instance.workflow.name }}</h3>
            <p class="step-name">Step: {{ approval.step_name }}</p>
          </div>
          <span :class="['status-badge', `status-${approval.status}`]">
            {{ approval.status }}
          </span>
        </div>

        <div class="approval-details">
          <div class="detail-item">
            <span class="label">Related Entity:</span>
            <span>{{ getEntityName(approval.workflow_instance.workflowable) }}</span>
          </div>
          <div class="detail-item">
            <span class="label">Requested:</span>
            <span>{{ formatDate(approval.created_at) }}</span>
          </div>
        </div>

        <div v-if="approval.status === 'pending'" class="approval-actions">
          <button
            @click="approve(approval.id)"
            class="btn-success"
          >
            Approve
          </button>
          <button
            @click="showRejectModal(approval.id)"
            class="btn-danger"
          >
            Reject
          </button>
        </div>

        <div v-if="approval.comments" class="approval-comments">
          <strong>Comments:</strong>
          <p>{{ approval.comments }}</p>
        </div>
      </div>
    </div>

    <Pagination :links="approvals.links" />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  approvals: Object,
  filters: Object,
});

const filters = ref({ ...props.filters });

const pendingCount = computed(() => {
  return props.approvals.data.filter(a => a.status === 'pending').length;
});

const search = () => {
  router.get('/approvals', filters.value, {
    preserveState: true,
    replace: true,
  });
};

const approve = (approvalId) => {
  if (confirm('Are you sure you want to approve this request?')) {
    router.post(`/approvals/${approvalId}/approve`, {}, {
      preserveScroll: true,
    });
  }
};

const showRejectModal = (approvalId) => {
  const comments = prompt('Please provide a reason for rejection:');
  if (comments) {
    router.post(`/approvals/${approvalId}/reject`, { comments }, {
      preserveScroll: true,
    });
  }
};

const getEntityName = (entity) => {
  if (entity.full_name) return entity.full_name;
  if (entity.transaction_number) return entity.transaction_number;
  return 'Unknown';
};

const formatDate = (date) => {
  return new Date(date).toLocaleDateString();
};
</script>

<style scoped>
.approvals-index {
  padding: 2rem;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.pending-count {
  background: #fef3c7;
  color: #92400e;
  padding: 0.5rem 1rem;
  border-radius: 1rem;
  font-weight: 500;
}

.approvals-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.approval-card {
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  padding: 1.5rem;
  background: white;
}

.approval-actions {
  display: flex;
  gap: 1rem;
  margin-top: 1rem;
}

.btn-success {
  background: #10b981;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  border: none;
  cursor: pointer;
}

.btn-danger {
  background: #ef4444;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
  border: none;
  cursor: pointer;
}
</style>