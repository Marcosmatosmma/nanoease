<script setup>
import { Icon } from '@iconify/vue'
import { Link } from '@inertiajs/vue3'
import { useColorMode } from '@vueuse/core'
import { computed, inject } from 'vue'
import {
  SidebarContent,
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/Components/ui/sidebar'

const route = inject('route')
const mode = useColorMode({
  attribute: 'class',
  modes: { light: '', dark: 'dark' },
})

const navigationConfig = [
  {
    label: 'Plataforma',
    items: [
      {
        name: 'Dashboard',
        icon: 'lucide:layout-dashboard',
        route: 'dashboard',
      },
      {
        name: 'Automações',
        icon: 'lucide:sparkles',
        route: 'automations.index',
      },
      {
        name: 'Tarefas',
        icon: 'lucide:kanban-square',
        route: 'tasks.index',
      },
      {
        name: 'Contratos',
        icon: 'lucide:file-text',
        route: 'contracts.index',
      },
      {
        name: 'E-mails Organizados',
        icon: 'lucide:inbox',
        route: 'emails.organized',
      },
      {
        name: 'Integrações',
        icon: 'lucide:link-2',
        route: 'integrations.index',
      },
      {
        name: 'Configurações',
        icon: 'lucide:settings',
        route: 'profile.show',
      },
    ],
  },
]

const isDarkMode = computed(() => mode.value === 'dark')

function renderLink(item) {
  if (item.external) {
    return {
      is: 'a',
      href: item.href || route(item.route),
      target: '_blank',
    }
  }
  return {
    is: Link,
    href: route(item.route),
  }
}
</script>

<template>
  <SidebarContent>
    <SidebarGroup
      v-for="(group, index) in navigationConfig"
      :key="index"
      :class="group.class"
    >
      <SidebarGroupLabel v-if="group.label">
        {{ group.label }}
      </SidebarGroupLabel>
      <SidebarMenu>
        <SidebarMenuItem
          v-for="item in group.items"
          :key="item.name"
          :class="{
            'font-semibold text-primary bg-secondary rounded':
              !item.external && route().current(item.route),
          }"
        >
          <SidebarMenuButton as-child>
            <component
              v-bind="renderLink(item)"
              :is="item.external ? 'a' : Link"
              prefetch
            >
              <Icon :icon="item.icon" />
              {{ item.name }}
            </component>
          </SidebarMenuButton>
        </SidebarMenuItem>
        <SidebarMenuItem v-if="index === navigationConfig.length - 1">
          <SidebarMenuButton
            @click="mode = isDarkMode ? 'light' : 'dark'"
          >
            <Icon
              :icon="isDarkMode ? 'lucide:moon' : 'lucide:sun'"
            />
            {{ isDarkMode ? "Dark" : "Light" }} Mode
          </SidebarMenuButton>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarGroup>
  </SidebarContent>
</template>
