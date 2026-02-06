<script setup>
import { Icon } from '@iconify/vue'
import { Link } from '@inertiajs/vue3'
import FeaturesCard from '@/Components/FeaturesCard.vue'
import PricingCard from '@/Components/PricingCard.vue'
import Terminal from '@/Components/Terminal.vue'
import Accordion from '@/Components/ui/accordion/Accordion.vue'
import AccordionContent from '@/Components/ui/accordion/AccordionContent.vue'
import AccordionItem from '@/Components/ui/accordion/AccordionItem.vue'
import AccordionTrigger from '@/Components/ui/accordion/AccordionTrigger.vue'
import Badge from '@/Components/ui/badge/Badge.vue'
import Button from '@/Components/ui/button/Button.vue'
import { useSeoMetaTags } from '@/composables/useSeoMetaTags.js'
import WebLayout from '@/Layouts/WebLayout.vue'

const props = defineProps({
  canLogin: {
    type: Boolean,
  },
  canRegister: {
    type: Boolean,
  },
  seo: {
    type: Object,
    default: () => null,
  },
  plans: {
    type: Array,
    default: () => [],
  },
})

useSeoMetaTags(props.seo)

const features = [
  {
    icon: '🚀',
    title: 'Experiência Dev 10x',
    description:
            'Entregue mais rápido com Laravel Pint, PHPStan nível máximo e Rector para qualidade de código e produtividade aprimoradas.',
  },
  {
    icon: '🐳',
    title: 'Docker Pronto para Produção',
    description:
            'Imagens Docker otimizadas com Laravel Octane para desenvolvimento e deployment ultra-rápidos.',
  },
  {
    icon: '🔑',
    title: 'Autenticação Avançada',
    description:
            'Sistema completo de autenticação com login social e controle de acesso baseado em funções (RBAC).',
  },
  {
    icon: '💳',
    title: 'Pagamentos Prontos',
    description:
            'Laravel Cashier integrado para cobrança de assinaturas e processamento de pagamentos para que você foque no produto.',
  },
  {
    icon: '🌐',
    title: 'Pronto para API',
    description:
            'Endpoints API RESTful com autenticação Laravel Sanctum e documentação abrangente.',
  },
  {
    icon: '🎨',
    title: 'Interface Customizável',
    description:
            'Construído com componentes shadcn/ui, tornando a customização fácil. Modifique temas, estilos e componentes para sua marca.',
  },
  {
    icon: '🧠',
    title: 'Integração com IA',
    description:
            'Integrações LLM pré-configuradas para OpenAI, Anthropic e mais. Crie recursos de IA no seu app com configuração mínima.',
  },
  {
    icon: '📊',
    title: 'Admin FilamentPHP',
    description:
            'Lindo painel administrativo powered by FilamentPHP com operações CRUD, gráficos e análises detalhadas.',
  },
  {
    icon: '✨',
    title: 'Recursos em Evolução',
    description:
            'Isso é apenas o começo. Atualizações regulares trazem novos recursos, integrações e melhorias para turbinar seu desenvolvimento.',
  },
]

const pricingFeatures = [
  'Setup Docker pronto para produção',
  'Sistema de autenticação avançado',
  'Integrações de IA',
  'Integração de pagamentos pronta',
  'Endpoints de API com Sanctum',
  'Documentação abrangente',
]
const sponsorLinks = {
  github: 'https://github.com/sponsors/pushpak1300',
  x: 'https://x.com/pushpak1300',
}

const faqItems = [
  {
    value: 'item-1',
    title: 'O Larasonic é realmente gratuito?',
    content:
            'Sim! O Larasonic é completamente gratuito e open source sob a licença MIT. Você pode usá-lo para projetos pessoais ou comerciais sem restrições.',
  },
  {
    value: 'item-2',
    title: 'Como posso contribuir?',
    content:
            'Você pode contribuir enviando pull requests, reportando bugs, sugerindo recursos ou ajudando na documentação. Toda contribuição é valiosa!',
  },
  {
    value: 'item-3',
    title: 'Por que devo patrocinar?',
    content:
            'Patrocinar ajuda a garantir a sustentabilidade de longo prazo do projeto. Seu suporte permite manutenção contínua e novos recursos.',
  },
]

const githubUrl = 'https://github.com/shipfastlabs/larasonic-vue'
</script>

<template>
  <WebLayout :can-login="canLogin" :can-register="canRegister">
    <!-- Hero Section -->
    <section
      class="relative overflow-hidden border-b bg-background py-20 sm:py-32"
    >
      <div class="container mx-auto px-4 text-center">
        <!-- Badge -->
        <div class="mb-8 inline-flex justify-center">
          <Badge
            variant="outline"
            class="rounded-full border bg-primary/10 px-4 py-1 text-xs sm:text-sm"
          >
            ✨ Using PHP 8.3+, Laravel 11, Inertia 2.0 and Tailwind
            CSS 4+
          </Badge>
        </div>

        <!-- Main Heading -->
        <div class="mx-auto max-w-4xl">
          <h1
            class="text-4xl font-extrabold tracking-tight sm:text-5xl md:text-6xl lg:text-7xl"
            :style="{ contain: 'layout paint' }"
          >
            <span class="block text-foreground">O Melhor Sistema</span>
            <span
              class="mt-2 block bg-linear-to-r from-red-500 via-purple-500 to-indigo-500 bg-clip-text text-transparent"
            >
              Para o Seu Negócio
            </span>
          </h1>
        </div>

        <!-- Subtitle - Add priority hint -->
        <p
          class="mx-auto mt-6 max-w-2xl text-center text-base text-muted-foreground sm:text-lg md:text-xl"
          :style="{ contain: 'layout paint' }"
          fetchpriority="high"
        >
          Gerencie seus contratos, automações e equipe com facilidade.
          Comece seu teste grátis de 14 dias hoje.
        </p>

        <!-- CTA Buttons -->
        <div
          class="mt-10 flex items-center justify-center gap-4 flex-row"
        >
          <Button
            as="a"
            href="https://larasonic.com/dashboard"
            target="_blank"
            size="lg"
            class="w-full sm:w-auto"
          >
            Ver Demonstração
          </Button>
          <Button
            as="a"
            :href="githubUrl"
            target="_blank"
            size="lg"
            variant="outline"
            class="w-full sm:w-auto"
          >
            <Icon
              icon="lucide:github"
              class="size-4"
              aria-hidden="true"
            />
            Github
          </Button>
        </div>

        <!-- Trust Badges -->
        <div class="mt-16 sm:mt-24">
          <p class="text-sm text-muted-foreground">
            Confiado por desenvolvedores no mundo todo
          </p>
          <div
            class="mt-4 flex flex-wrap items-center justify-center gap-6 sm:gap-8"
          >
            <Icon
              icon="logos:laravel"
              class="size-8 opacity-75 grayscale transition-all hover:opacity-100 hover:grayscale-0"
            />
            <Icon
              icon="logos:vue"
              class="size-8 opacity-75 grayscale transition-all hover:opacity-100 hover:grayscale-0"
            />
            <Icon
              icon="simple-icons:inertia"
              class="size-8 opacity-75 grayscale transition-all hover:opacity-100 hover:grayscale-0 text-purple-500"
            />
            <Icon
              icon="logos:tailwindcss-icon"
              class="size-8 opacity-75 grayscale transition-all hover:opacity-100 hover:grayscale-0"
            />
          </div>
        </div>
      </div>

      <!-- Background Effects -->
      <div
        class="absolute inset-0 -z-10 h-full w-full bg-[linear-gradient(to_right,#4f4f4f2e_1px,transparent_1px),linear-gradient(to_bottom,#4f4f4f2e_1px,transparent_1px)] bg-[size:14px_24px]"
      />
      <div
        class="absolute left-0 right-0 top-0 -z-10 m-auto h-[310px] w-[310px] rounded-full bg-primary/20 opacity-20 blur-[100px]"
      />
    </section>
    <!-- Features Grid -->
    <section
      id="features"
      class="container mx-auto px-4 py-16 sm:px-6 lg:px-8"
    >
      <h2
        class="text-center text-2xl font-bold tracking-tight sm:text-4xl"
      >
        Recursos ✨
      </h2>
      <p class="mx-auto mt-4 max-w-2xl text-center text-muted-foreground">
        Tudo o que você precisa para entregar mais rápido para produção sem complicações.
      </p>

      <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
        <FeaturesCard
          v-for="feature in features"
          :key="feature.title"
          :icon="feature.icon"
          :title="feature.title"
          :description="feature.description"
        />
      </div>
      <div class="mt-6 flex justify-center gap-2">
        <Button
          as="a"
          href="https://docs.larasonic.com"
          target="_blank"
          rel="noopener noreferrer"
        >
          <Icon
            icon="lucide:book-open"
            class="size-4"
            aria-hidden="true"
          />
          Documentação
        </Button>
        <Button
          variant="secondary"
          as="a"
          :href="`${githubUrl}/discussions/categories/roadmap`"
          target="_blank"
          rel="noopener noreferrer"
        >
          <Icon
            icon="lucide:construction"
            class="size-4"
            aria-hidden="true"
          />
          Roadmap
        </Button>
      </div>
    </section>

    <section id="pricing" class="border-t">
      <div class="container mx-auto px-4 py-16 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mx-auto max-w-3xl text-center">
          <h2
            class="text-center text-2xl font-bold tracking-tight sm:text-4xl"
          >
            Orgulhosamente Open Source 🤑
          </h2>
          <p
            class="mx-auto mt-4 max-w-2xl text-center text-muted-foreground"
          >
            Larasonic é e sempre será open source. Sem taxas escondidas, sem recursos premium.
          </p>
        </div>

        <!-- Pricing Cards -->
        <div class="mt-16 grid gap-8 sm:grid-cols-1 lg:grid-cols-2 max-w-5xl mx-auto">
          <PricingCard
            v-for="plan in plans"
            :key="plan.id"
            class="mx-auto"
            :features="plan.features || []"
            :price="Number(plan.price) || 0"
            :plan="plan.name"
            :description="plan.description"
            :billing-period="plan.price ? 'Mês' : ''"
          >
            <template #pricing v-if="!plan.price">
              <div>
                <h4 class="text-3xl font-bold">Fale Conosco</h4>
                <p class="text-sm text-muted-foreground mt-2">
                  Sob medida para seu negócio
                </p>
              </div>
            </template>

            <template #action>
              <Button
                v-if="plan.price"
                :as="Link"
                :href="route('register')"
                class="w-full"
              >
                Teste Grátis de 14 Dias
              </Button>
              <Button
                v-else
                as="a"
                href="mailto:contact@larasonic.com"
                variant="outline"
                class="w-full"
              >
                Falar com Vendas
              </Button>
            </template>
          </PricingCard>
        </div>
        <!-- FAQ Section -->
        <div class="mx-auto mt-16 text-center">
          <h2 class="text-2xl font-bold">
            Perguntas Frequentes
          </h2>
          <Accordion
            type="single"
            class="mt-8 w-full text-left"
            collapsible
            default-value="item-1"
          >
            <AccordionItem
              v-for="item in faqItems"
              :key="item.value"
              :value="item.value"
            >
              <AccordionTrigger class="text-lg">
                {{ item.title }}
              </AccordionTrigger>
              <AccordionContent>
                {{ item.content }}
              </AccordionContent>
            </AccordionItem>
          </Accordion>
        </div>
      </div>
    </section>

    <!-- CTA Section -->
    <section class="border-t">
      <div class="container mx-auto px-4 py-16 sm:px-6 lg:px-8">
        <div class="rounded-2xl px-6 py-12 sm:p-16">
          <div class="mx-auto max-w-2xl text-center">
            <h2
              class="text-3xl font-bold tracking-tight sm:text-6xl"
            >
              Pronto para entregar mais rápido?
            </h2>
            <p class="mx-auto mt-4 max-w-xl text-lg">
              Você já é rápido com Laravel.<br>
              Larasonic vai tornar sua entrega supersônica. 🚀
            </p>
            <div class="mt-8 flex justify-center gap-4">
              <Button
                as="a"
                :href="githubUrl"
                target="_blank"
                rel="noopener noreferrer"
              >
                Ver no GitHub
              </Button>
            </div>
          </div>
        </div>
        <div
          class="mx-auto w-full sm:w-2/3 items-center justify-center"
        >
          <Terminal />
        </div>
      </div>
    </section>
  </WebLayout>
</template>
