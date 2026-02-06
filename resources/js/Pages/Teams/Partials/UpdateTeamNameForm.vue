<script setup>
import { useForm } from '@inertiajs/vue3'
import { inject } from 'vue'
import { toast } from 'vue-sonner'
import FormSection from '@/Components/FormSection.vue'
import InputError from '@/Components/InputError.vue'
import Avatar from '@/Components/ui/avatar/Avatar.vue'
import AvatarFallback from '@/Components/ui/avatar/AvatarFallback.vue'

import AvatarImage from '@/Components/ui/avatar/AvatarImage.vue'
import Button from '@/Components/ui/button/Button.vue'
import Input from '@/Components/ui/input/Input.vue'
import Label from '@/Components/ui/label/Label.vue'

const props = defineProps({
  team: Object,
  permissions: Object,
})
const route = inject('route')
const form = useForm({
  name: props.team.name,
})

function updateTeamName() {
  form.put(route('teams.update', props.team), {
    errorBag: 'updateTeamName',
    preserveScroll: true,
    onSuccess: () => toast.success('Nome da equipe atualizado com sucesso'),
  })
}
</script>

<template>
  <FormSection @submitted="updateTeamName">
    <template #title>
      Nome da Equipe
    </template>

    <template #description>
      O nome da equipe e informações do proprietário.
    </template>

    <template #form>
      <!-- Team Owner Information -->
      <div class="col-span-6">
        <Label value="Proprietário da Equipe" />

        <div class="mt-2 flex items-center">
          <Avatar>
            <AvatarImage
              :src="team.owner.profile_photo_path ?? ''"
              alt="profile photo"
            />
            <AvatarFallback class="rounded-full bg-secondary p-2">
              {{ team.name.charAt(0) }}
            </AvatarFallback>
          </Avatar>

          <div class="ms-4 leading-tight">
            <div class="">
              {{ team.owner.name }}
            </div>
            <div class="text-sm">
              {{ team.owner.email }}
            </div>
          </div>
        </div>
      </div>

      <!-- Team Name -->
      <div class="col-span-6 sm:col-span-4">
        <Label for="name">Nome da Equipe</Label>

        <Input
          id="name"
          v-model="form.name"
          type="text"
          class="mt-1 block w-full"
          :disabled="!permissions.canUpdateTeam"
        />

        <InputError :message="form.errors.name" class="mt-2" />
      </div>
    </template>

    <template v-if="permissions.canUpdateTeam" #actions>
      <Button
        :class="{ 'opacity-25': form.processing }"
        :disabled="form.processing"
      >
        Salvar
      </Button>
    </template>
  </FormSection>
</template>
