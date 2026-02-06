<script setup>
import { Icon } from '@iconify/vue'
import { useForm } from '@inertiajs/vue3'
import { inject } from 'vue'
import { toast } from 'vue-sonner'
import ActionSection from '@/components/ActionSection.vue'
import ConfirmsPassword from '@/components/ConfirmsPassword.vue'
import Button from '@/components/ui/button/Button.vue'

defineProps({
  sessions: Array,
})

const route = inject('route')
const form = useForm({
  password: '',
})

function logoutOtherBrowserSessions(password) {
  form.transform(data => ({
    ...data,
    password,
  })).delete(route('other-browser-sessions.destroy'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      toast.success('Saiu de outras sessões de navegador')
    },
    onFinish: () => form.reset(),
  })
}
</script>

<template>
  <ActionSection>
    <template #title>
      Sessões de Navegador
    </template>

    <template #description>
      Gerencie e saia de suas sessões ativas em outros navegadores e dispositivos.
    </template>

    <template #content>
      <div class="max-w-xl text-sm">
        Se necessário, você pode sair de todas as suas outras sessões de navegador em todos os seus dispositivos.
        Algumas de suas sessões recentes estão listadas abaixo; no entanto, esta lista pode não ser exaustiva.
        Se você achar que sua conta foi comprometida, você também deve atualizar sua senha.
      </div>

      <!-- Other Browser Sessions -->
      <div v-if="sessions.length > 0" class="mt-5 space-y-6">
        <div
          v-for="(session, i) in sessions"
          :key="i"
          class="flex items-center"
        >
          <div>
            <Icon
              v-if="session.agent.is_desktop"
              icon="lucide:laptop"
              class="size-8"
            />
            <Icon
              v-else
              icon="lucide:tablet-smartphone"
              class="size-8"
            />
          </div>

          <div class="ms-3">
            <div class="text-sm">
              {{
                session.agent.platform
                  ? session.agent.platform
                  : "Desconhecido"
              }}
              -
              {{
                session.agent.browser
                  ? session.agent.browser
                  : "Desconhecido"
              }}
            </div>

            <div>
              <div class="text-xs">
                {{ session.ip_address }},

                <span
                  v-if="session.is_current_device"
                  class="font-semibold text-green-400"
                >Este dispositivo</span>
                <span v-else>Última atividade {{ session.last_active }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-5 flex items-center">
        <ConfirmsPassword
          title="Sair de Outras Sessões de Navegador"
          content="Por favor, insira sua senha para confirmar que deseja sair de suas outras sessões de navegador em todos os seus dispositivos."
          button="Sair de Outras Sessões de Navegador"
          @confirmed="logoutOtherBrowserSessions"
        >
          <Button> Sair de Outras Sessões de Navegador </Button>
        </ConfirmsPassword>
      </div>
    </template>
  </ActionSection>
</template>
