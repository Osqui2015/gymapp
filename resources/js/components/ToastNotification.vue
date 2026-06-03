<template>
  <Teleport to="body">
    <div class="toast-container">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="toast"
          :class="toast.type"
        >
          <span v-if="toast.type === 'success'" class="toast-icon">✓</span>
          <span v-if="toast.type === 'error'" class="toast-icon">✕</span>
          <span v-if="toast.type === 'info'" class="toast-icon">ℹ</span>
          <span v-if="toast.type === 'warning'" class="toast-icon">⚠</span>
          <span class="toast-message">{{ toast.message }}</span>
          <button @click="removeToast(toast.id)" class="toast-close">×</button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { ref } from 'vue';

const toasts = ref([]);
let toastId = 0;

const addToast = (message, type = 'info', duration = 3000) => {
  const id = ++toastId;
  toasts.value.push({ id, message, type });

  if (duration > 0) {
    setTimeout(() => removeToast(id), duration);
  }
};

const removeToast = (id) => {
  const index = toasts.value.findIndex((t) => t.id === id);
  if (index > -1) {
    toasts.value.splice(index, 1);
  }
};

// Métodos públicos
const success = (message, duration) => addToast(message, 'success', duration);
const error = (message, duration) => addToast(message, 'error', duration);
const info = (message, duration) => addToast(message, 'info', duration);
const warning = (message, duration) => addToast(message, 'warning', duration);

defineExpose({ success, error, info, warning, addToast });
</script>

<style scoped>
.toast-container {
  position: fixed;
  top: 1rem;
  right: 1rem;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  max-width: 400px;
}

.toast {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem 1.25rem;
  border-radius: 0.75rem;
  box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
  font-size: 0.875rem;
  font-weight: 500;
  backdrop-filter: blur(8px);
}

.toast.success {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
}

.toast.error {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: white;
}

.toast.info {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: white;
}

.toast.warning {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white;
}

.toast-icon {
  font-size: 1.25rem;
  font-weight: bold;
}

.toast-message {
  flex: 1;
}

.toast-close {
  background: none;
  border: none;
  color: white;
  font-size: 1.5rem;
  cursor: pointer;
  opacity: 0.7;
  padding: 0;
  line-height: 1;
}

.toast-close:hover {
  opacity: 1;
}

/* Transiciones */
.toast-enter-active {
  animation: slideIn 0.3s ease-out;
}

.toast-leave-active {
  animation: slideOut 0.3s ease-in;
}

@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@keyframes slideOut {
  from {
    transform: translateX(0);
    opacity: 1;
  }
  to {
    transform: translateX(100%);
    opacity: 0;
  }
}
</style>