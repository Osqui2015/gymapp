<template>
  <Teleport to="body">
    <div class="toast-container">
      <TransitionGroup name="toast" tag="div" class="flex flex-col gap-2">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="toast-item"
          :class="[`toast-${toast.type}`]"
          role="status"
          :aria-live="toast.type === 'error' ? 'assertive' : 'polite'"
        >
          <!-- Icono -->
          <div class="toast-icon-wrapper">
            <svg v-if="toast.type === 'success'" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            <svg v-else-if="toast.type === 'error'" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <svg v-else-if="toast.type === 'warning'" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5 19h14a2 2 0 001.732-3L13.732 4a2 2 0 00-3.464 0L3.268 16A2 2 0 005 19z" />
            </svg>
            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>

          <!-- Contenido -->
          <div class="toast-body">
            <p v-if="toast.title" class="toast-title">{{ toast.title }}</p>
            <p class="toast-message">{{ toast.message }}</p>
          </div>

          <!-- Acción opcional (undo) -->
          <button
            v-if="toast.action"
            @click="runAction(toast)"
            class="toast-action"
            type="button"
          >
            {{ toast.action.label }}
          </button>

          <!-- Cerrar -->
          <button
            @click="dismiss(toast.id)"
            class="toast-close"
            aria-label="Cerrar notificación"
            type="button"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>

          <!-- Barra de progreso (auto-dismiss) -->
          <div
            v-if="!toast.persistent && toast.duration > 0"
            class="toast-progress"
            :style="{ animationDuration: `${toast.duration}ms` }"
          ></div>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';
import { useToastStore } from '../stores/toast';

const store = useToastStore();
const toasts = computed(() => store.toasts);
const dismiss = (id) => store.dismiss(id);
const runAction = (toast) => {
    if (toast.action?.onClick) toast.action.onClick();
    dismiss(toast.id);
};
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
  max-width: 24rem;
  width: calc(100% - 2rem);
  pointer-events: none;
}

.toast-item {
  position: relative;
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.875rem 1rem;
  border-radius: 0.75rem;
  box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
  font-size: 0.875rem;
  font-weight: 500;
  pointer-events: auto;
  overflow: hidden;
  border: 1px solid transparent;
  min-height: 3rem;
}

.toast-icon-wrapper {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.5rem;
  height: 1.5rem;
  margin-top: 0.05rem;
}

.toast-body {
  flex: 1;
  min-width: 0;
}

.toast-title {
  font-weight: 700;
  font-size: 0.9rem;
  margin-bottom: 0.125rem;
}

.toast-message {
  word-wrap: break-word;
  line-height: 1.4;
}

.toast-action {
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: inherit;
  font-weight: 700;
  font-size: 0.75rem;
  padding: 0.25rem 0.625rem;
  border-radius: 0.375rem;
  cursor: pointer;
  flex-shrink: 0;
  text-transform: uppercase;
  letter-spacing: 0.025em;
  transition: background 150ms ease;
}

.toast-action:hover {
  background: rgba(255, 255, 255, 0.3);
}

.toast-close {
  background: none;
  border: none;
  color: inherit;
  cursor: pointer;
  opacity: 0.7;
  padding: 0.125rem;
  border-radius: 0.25rem;
  flex-shrink: 0;
  transition: opacity 150ms ease;
  display: flex;
  align-items: center;
  justify-content: center;
}

.toast-close:hover {
  opacity: 1;
  background: rgba(255, 255, 255, 0.15);
}

/* Tipos */
.toast-success {
  background: linear-gradient(135deg, #10b981, #059669);
  color: white;
  border-color: rgba(255, 255, 255, 0.15);
}

.toast-error {
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: white;
  border-color: rgba(255, 255, 255, 0.15);
}

.toast-warning {
  background: linear-gradient(135deg, #f59e0b, #d97706);
  color: white;
  border-color: rgba(255, 255, 255, 0.15);
}

.toast-info {
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  color: white;
  border-color: rgba(255, 255, 255, 0.15);
}

/* Barra de progreso de auto-dismiss */
.toast-progress {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  background: rgba(255, 255, 255, 0.45);
  width: 100%;
  transform-origin: left;
  animation: toast-shrink linear forwards;
}

@keyframes toast-shrink {
  from { transform: scaleX(1); }
  to   { transform: scaleX(0); }
}

/* Transiciones entrada/salida */
.toast-enter-active {
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
}

.toast-leave-active {
  transition: all 0.25s ease-in;
  position: absolute;
  right: 0;
  width: 100%;
}

.toast-enter-from {
  transform: translateX(110%);
  opacity: 0;
}

.toast-leave-to {
  transform: translateX(110%);
  opacity: 0;
}

.toast-move {
  transition: transform 0.3s ease;
}

/* Dark mode: las clases de Tailwind ya manejan el fondo del body, los toasts mantienen su gradiente */
</style>
