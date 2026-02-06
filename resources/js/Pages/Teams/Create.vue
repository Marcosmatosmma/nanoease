<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import CreateTeamForm from '@/Pages/teams/Partials/CreateTeamForm.vue'
</script>

<template>
  <AppLayout title="Criar Equipe">
    <template #header>
      <h2
        class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200"
      >
        Criar Equipe
      </h2>
    </template>

    <div>
      <div class="max-w-7xl">
        <CreateTeamForm />
      </div>
    </div>
  </AppLayout>
</template>
