<script setup>
import { useForm } from '@inertiajs/vue3'
import { inject } from 'vue'
import ActionSection from '@/components/ActionSection.vue'
import ConfirmsPassword from '@/components/ConfirmsPassword.vue'
import Button from '@/components/ui/button/Button.vue'

const route = inject('route')
const form = useForm({})

function deleteUser(password) {
  form.transform(data => ({
    ...data,
    password,
  })).delete(route('current-user.destroy'), {
    preserveScroll: true,
    onSuccess: () => form.reset(),
    onFinish: () => form.reset(),
  })
}
</script>

<template>
  <ActionSection>
    <template #title>
      Excluir Conta
    </template>

    <template #description>
      Excluir permanentemente sua conta.
    </template>

    <template #content>
      <div class="max-w-xl text-sm text-gray-600 dark:text-gray-400">
        Uma vez que sua conta for excluída, todos os seus recursos e dados serão excluídos permanentemente.
        Antes de excluir sua conta, faça o download de quaisquer dados ou informações que deseja manter.
      </div>

      <div class="mt-5">
        <ConfirmsPassword
          title="Excluir Conta"
          content="Tem certeza de que deseja excluir sua conta? Uma vez que sua conta for excluída, todos os seus recursos e dados serão excluídos permanentemente. Por favor, insira sua senha para confirmar."
          button="Excluir Conta"
          @confirmed="deleteUser"
        >
          <Button variant="destructive">
            Excluir Conta
          </Button>
        </ConfirmsPassword>
      </div>
    </template>
  </ActionSection>
</template>
