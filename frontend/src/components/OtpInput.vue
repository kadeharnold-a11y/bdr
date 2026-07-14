<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  length: { type: Number, default: 6 },
  hasError: { type: Boolean, default: false },
  mask: { type: Boolean, default: false }, // hide digits (for PIN entry)
})

const emit = defineEmits(['update:modelValue', 'complete'])

const inputs = ref([])

function digitsFrom(value) {
  return (value || '').replace(/\D/g, '').slice(0, props.length).split('')
}

function emitValue(digits) {
  const joined = digits.join('')
  emit('update:modelValue', joined)
  if (joined.length === props.length) emit('complete', joined)
}

function onInput(index, event) {
  const raw = event.target.value.replace(/\D/g, '')
  const digits = digitsFrom(props.modelValue)

  if (!raw) {
    digits[index] = ''
    emitValue(digits)
    return
  }

  // Take the last typed character (handles overwrite of a filled box).
  digits[index] = raw[raw.length - 1]
  emitValue(digits)

  if (index < props.length - 1) focusInput(index + 1)
}

function onKeydown(index, event) {
  if (event.key === 'Backspace') {
    // Manage backspace explicitly so state stays authoritative even when the
    // native delete doesn't emit an input event (controlled value binding).
    event.preventDefault()
    const digits = digitsFrom(props.modelValue)
    if (digits[index]) {
      digits[index] = ''
      emitValue(digits)
    } else if (index > 0) {
      digits[index - 1] = ''
      emitValue(digits)
      focusInput(index - 1)
    }
  } else if (event.key === 'ArrowLeft' && index > 0) {
    focusInput(index - 1)
  } else if (event.key === 'ArrowRight' && index < props.length - 1) {
    focusInput(index + 1)
  }
}

function onPaste(event) {
  event.preventDefault()
  const pasted = digitsFrom(event.clipboardData.getData('text'))
  if (!pasted.length) return
  emitValue(pasted)
  focusInput(Math.min(pasted.length, props.length - 1))
}

function focusInput(index) {
  // Focus synchronously: all boxes are already mounted, so moving focus during
  // the input handler routes the next keystroke to the correct box (needed for
  // fast typing / autofill, where a deferred focus would drop digits).
  inputs.value[index]?.focus()
  inputs.value[index]?.select()
}

// Keep the boxes in sync if the parent clears/sets the value externally.
const boxes = ref(Array.from({ length: props.length }, () => ''))
watch(
  () => props.modelValue,
  (value) => {
    const digits = digitsFrom(value)
    boxes.value = Array.from({ length: props.length }, (_, i) => digits[i] || '')
  },
  { immediate: true },
)

defineExpose({ focus: () => focusInput(0) })
</script>

<template>
  <div class="otp-input" :class="{ 'has-error': hasError }">
    <input
      v-for="(box, i) in boxes"
      :key="i"
      :ref="(el) => (inputs[i] = el)"
      :value="box"
      class="otp-box"
      :type="mask ? 'password' : 'text'"
      inputmode="numeric"
      :autocomplete="mask ? 'off' : 'one-time-code'"
      maxlength="1"
      :aria-label="mask ? 'PIN digit' : 'Verification code digit'"
      @input="onInput(i, $event)"
      @keydown="onKeydown(i, $event)"
      @paste="onPaste"
    />
  </div>
</template>

<style scoped>
.otp-input {
  display: flex;
  gap: 10px;
}

.otp-box {
  width: 100%;
  aspect-ratio: 1 / 1;
  max-width: 56px;
  text-align: center;
  font-size: 22px;
  font-weight: 600;
  color: var(--text-primary);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  outline: none;
  transition: border-color 150ms ease, box-shadow 150ms ease;
}

.otp-box:focus {
  border-color: var(--brand-green);
  box-shadow: 0 0 0 3px rgba(0, 107, 63, 0.12);
}

.otp-input.has-error .otp-box {
  border-color: var(--danger);
}
</style>
