<script setup>
import { Icon } from '@iconify/vue'
import { router, usePage } from '@inertiajs/vue3'
import { useChangeCase } from '@vueuse/integrations/useChangeCase'
import { inject, onMounted, ref } from 'vue'
import { toast } from 'vue-sonner'
import ActionSection from '@/components/ActionSection.vue'
import Label from '@/components/ui/label/Label.vue'
import Switch from '@/components/ui/switch/Switch.vue'

const props = defineProps({
  availableProviders: {
    type: Object,
    default: () => {},
  },
  activeProviders: {
    type: Array,
    default: () => [],
  },
})

const route = inject('route')

const page = usePage()

onMounted(() => {
  if (page.props.flash.success) {
    toast.success(page.props.flash.success)
  }
  if (page.props.flash.error) {
    toast.error(page.props.flash.error)
  }
})

const loadingProvider = ref(null)

function toggleLink(provider) {
  loadingProvider.value = provider
  if (!props.activeProviders.includes(provider)) {
    toast.promise(new Promise(resolve => setTimeout(resolve, 1000)), {
      loading: 'Redirecionando para o provedor...',
    })
    window.location.href = route('oauth.redirect', { provider })
    return
  }

  router.delete(route('oauth.destroy', { provider }), {
    async: true,
    preserveScroll: true,
    onBefore: () => {
      toast.promise(new Promise(resolve => setTimeout(resolve, 1000)), {
        loading: 'Desconectando conta...',
      })
    },
    onSuccess: () => {
      setTimeout(() => {
        toast.success(page.props.flash.success)
      }, 1000)
    },
    onError: () => {
      toast.error(page.props.flash.message)
    },
    onFinish: () => {
      loadingProvider.value = null
    },
  })
}
</script>

<template>
  <ActionSection>
    <template #title>
      Contas Conectadas
    </template>

    <template #description>
      Gerencie suas contas sociais conectadas para facilitar o login.
    </template>

    <template #content>
      <div class="max-w-xl text-sm">
        Conecte suas contas para habilitar o login único e gerenciar seus perfis sociais conectados.
      </div>

      <div>
        <div
          v-for="provider in availableProviders"
          :key="provider.slug"
          class="flex items-center space-x-4 rounded-md border p-4 mt-4"
        >
          <Icon :icon="provider.icon" class="size-8" />
          <div class="flex-1 space-y-1">
            <Label>
              {{ useChangeCase(provider.slug, "sentenceCase") }}
            </Label>
          </div>
          <Switch
            :disabled="loadingProvider"
            :checked="activeProviders.includes(provider.slug)"
            @update:checked="toggleLink(provider.slug)"
            @update:unchecked="toggleLink(provider.slug)"
          />
        </div>
      </div>
    </template>
  </ActionSection>
</template>
