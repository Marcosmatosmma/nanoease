<script setup>
import { Link, router, useForm } from '@inertiajs/vue3'
import { inject, ref } from 'vue'
import { toast } from 'vue-sonner'
import FormSection from '@/components/FormSection.vue'
import InputError from '@/components/InputError.vue'
import Button from '@/components/ui/button/Button.vue'
import Input from '@/components/ui/input/Input.vue'
import Label from '@/components/ui/label/Label.vue'

const props = defineProps({
  user: Object,
})
const route = inject('route')
const form = useForm({
  _method: 'PUT',
  name: props.user.name,
  email: props.user.email,
  photo: null,
})

const verificationLinkSent = ref(null)
const photoPreview = ref(null)
const photoInput = ref(null)

  function updateProfileInformation() {
  if (photoInput.value) {
    form.photo = photoInput.value.files[0]
  }

  form.post(route('user-profile-information.update'), {
    errorBag: 'updateProfileInformation',
    preserveScroll: true,
    onSuccess: () => {
      clearPhotoFileInput()
      toast.success('Informações do perfil atualizadas')
    },
  })
}

function sendEmailVerification() {
  verificationLinkSent.value = true
}

function selectNewPhoto() {
  photoInput.value.click()
}

function updatePhotoPreview() {
  const photo = photoInput.value.files[0]

  if (!photo)
    return

  const reader = new FileReader()

  reader.onload = (e) => {
    photoPreview.value = e.target.result
  }

  reader.readAsDataURL(photo)
}

function deletePhoto() {
  router.delete(route('current-user-photo.destroy'), {
    preserveScroll: true,
    onSuccess: () => {
      photoPreview.value = null
      clearPhotoFileInput()
      toast.success('Foto removida')
    },
  })
}

function clearPhotoFileInput() {
  if (photoInput.value?.value) {
    photoInput.value.value = null
  }
}
</script>

<template>
  <FormSection @submitted="updateProfileInformation">
    <template #title>
      Informações do Perfil
    </template>

    <template #description>
      Atualize as informações do seu perfil e endereço de e-mail.
    </template>

    <template #form>
      <!-- Profile Photo -->
      <div
        v-if="$page.props.jetstream.managesProfilePhotos"
        class="col-span-6 sm:col-span-4"
      >
        <!-- Profile Photo File Input -->
        <input
          id="photo"
          ref="photoInput"
          type="file"
          class="hidden"
          @change="updatePhotoPreview"
        >

        <Label for="photo">Foto</Label>

        <!-- Current Profile Photo -->
        <div v-show="!photoPreview" class="mt-2">
          <img
            :src="user.profile_photo_url"
            :alt="user.name"
            class="size-20 rounded-full object-cover"
          >
        </div>

        <!-- New Profile Photo Preview -->
        <div v-show="photoPreview" class="mt-2">
          <span
            class="block size-20 rounded-full bg-cover bg-center bg-no-repeat"
            :style="`background-image: url('${photoPreview}');`"
          />
        </div>

        <Button
          variant="secondary"
          class="me-2 mt-2"
          type="button"
          @click.prevent="selectNewPhoto"
        >
          Selecionar Nova Foto
        </Button>

        <Button
          v-if="user.profile_photo_path"
          variant="secondary"
          type="button"
          class="mt-2"
          @click.prevent="deletePhoto"
        >
          Remover Foto
        </Button>

        <InputError :message="form.errors.photo" class="mt-2" />
      </div>

      <!-- Name -->
      <div class="col-span-6 sm:col-span-4">
        <Label for="name">Nome</Label>
        <Input
          id="name"
          v-model="form.name"
          type="text"
          class="mt-1 block w-full"
          required
          autocomplete="name"
        />
        <InputError :message="form.errors.name" class="mt-2" />
      </div>

      <!-- Email -->
      <div class="col-span-6 sm:col-span-4">
        <Label for="email">E-mail</Label>
        <Input
          id="email"
          v-model="form.email"
          disabled
          type="email"
          class="mt-1 block w-full"
          required
          autocomplete="username"
        />
        <InputError :message="form.errors.email" class="mt-2" />

        <div
          v-if="
            $page.props.jetstream.hasEmailVerification
              && user.email_verified_at === null
          "
        >
          <p class="mt-2 text-sm">
            Seu endereço de e-mail não foi verificado.

            <Link
              :href="route('verification.send')"
              method="post"
              as="button"
              class="rounded-md text-sm underline focus:outline-hidden focus:ring-2 focus:ring-offset-2"
              @click.prevent="sendEmailVerification"
            >
              Clique aqui para reenviar o e-mail de verificação.
            </Link>
          </p>

          <div
            v-show="verificationLinkSent"
            class="mt-2 text-sm font-medium"
          >
            Um novo link de verificação foi enviado para o seu endereço de e-mail.
          </div>
        </div>
      </div>
    </template>

    <template #actions>
      <Button
        :class="{ 'opacity-25': form.processing }"
        :disabled="form.processing"
      >
        Salvar
      </Button>
    </template>
  </FormSection>
</template>
