<script setup>
import axios from 'axios'
import { inject, reactive, ref } from 'vue'
import InputError from '@/Components/InputError.vue'
import Button from '@/Components/ui/button/Button.vue'
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/Components/ui/dialog'
import Input from '@/Components/ui/input/Input.vue'

defineProps({
  title: {
    type: String,
    default: 'Confirmar Senha',
  },
  content: {
    type: String,
    default: 'Para sua segurança, por favor confirme sua senha para continuar.',
  },
  button: {
    type: String,
    default: 'Confirmar',
  },
})

const emit = defineEmits(['confirmed'])
const route = inject('route')
const confirmingPassword = ref(false)

const form = reactive({
  password: '',
  error: '',
  processing: false,
})

const passwordInput = ref(null)
function confirmPassword() {
  form.processing = true

  axios
    .post(route('password.confirm'), {
      password: form.password,
    })
    .then(() => {
      form.processing = false
      emit('confirmed', form.password)
      closeModal()
    })
    .catch((error) => {
      form.processing = false
      form.error = error.response.data.errors.password[0]
      passwordInput.value.focus()
    })
}

function closeModal() {
  confirmingPassword.value = false
  form.password = ''
  form.error = ''
}
</script>

<template>
  <span>
    <Dialog v-model:open="confirmingPassword">
      <DialogTrigger>
        <slot />
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{{ title }}</DialogTitle>
          <DialogDescription>
            {{ content }}
          </DialogDescription>
        </DialogHeader>

        <div class="mt-4">
          <Input
            ref="passwordInput"
            v-model="form.password"
            type="password"
            class="mt-1 block"
            placeholder="Senha"
            autocomplete="current-password"
            @keyup.enter="confirmPassword"
          />

          <InputError :message="form.error" class="mt-2" />
        </div>

        <DialogFooter class="mt-4">
          <DialogClose as-child>
            <Button variant="secondary" @click="closeModal">
              Cancelar
            </Button>
          </DialogClose>

          <Button
            variant="destructive"
            class="ms-3"
            :class="{ 'opacity-25': form.processing }"
            :disabled="form.processing"
            @click="confirmPassword"
          >
            {{ button }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </span>
</template>
