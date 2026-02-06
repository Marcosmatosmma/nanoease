<script setup>
import { useForm } from '@inertiajs/vue3'
import { inject, ref } from 'vue'
import ActionSection from '@/Components/ActionSection.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import Button from '@/Components/ui/button/Button.vue'

const props = defineProps({
  team: Object,
})
const route = inject('route')
const confirmingTeamDeletion = ref(false)
const form = useForm({})

function confirmTeamDeletion() {
  confirmingTeamDeletion.value = true
}

function deleteTeam() {
  form.delete(route('teams.destroy', props.team), {
    errorBag: 'deleteTeam',
  })
}
</script>

<template>
  <ActionSection>
    <template #title>
      Excluir Equipe
    </template>

    <template #description>
      Excluir permanentemente esta equipe.
    </template>

    <template #content>
      <div class="max-w-xl text-sm text-gray-600 dark:text-gray-400">
        Uma vez que uma equipe é excluída, todos os seus recursos e dados serão
        excluídos permanentemente. Antes de excluir esta equipe, por favor, baixe
        quaisquer dados ou informações sobre esta equipe que você deseja manter.
      </div>

      <div class="mt-5">
        <Button variant="destructive" @click="confirmTeamDeletion">
          Excluir Equipe
        </Button>
      </div>

      <!-- Delete Team Confirmation Modal -->
      <ConfirmationModal
        :show="confirmingTeamDeletion"
        @close="confirmingTeamDeletion = false"
      >
        <template #title>
          Excluir Equipe
        </template>

        <template #content>
          Tem certeza de que deseja excluir esta equipe? Uma vez que uma equipe é
          excluída, todos os seus recursos e dados serão excluídos permanentemente.
        </template>

        <template #footer>
          <Button
            variant="secondary"
            @click="confirmingTeamDeletion = false"
          >
            Cancelar
          </Button>

          <Button
            variant="destructive"
            class="ms-3"
            :class="{ 'opacity-25': form.processing }"
            :disabled="form.processing"
            @click="deleteTeam"
          >
            Excluir Equipe
          </Button>
        </template>
      </ConfirmationModal>
    </template>
  </ActionSection>
</template>
