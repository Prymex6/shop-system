<template>
  <ManagerLayout :title="t('manager.homepagebuilder.index.homepage_builder')">
    <!-- Topbar -->
    <div class="flex items-center justify-between mb-4 -mt-2">
      <div>
        <h1 class="text-xl font-bold text-gray-900">{{ t('manager.homepagebuilder.index.homepage_builder') }}</h1>
        <p class="text-xs text-gray-500 mt-0.5">
          {{ t('manager.homepagebuilder.index.click_a_block_in_the_preview_2') }}
        </p>
      </div>
      <div class="flex items-center gap-2">
        <a
          :href="shopUrl"
          target="_blank"
          class="flex items-center gap-1.5 px-3 py-2 text-sm text-gray-600 border border-gray-200 rounded-lg hover:bg-gray-50 transition"
        >
          <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i> {{ t('common.preview_the_shop') }}
        </a>
        <button
          @click="save"
          :disabled="saving"
          class="flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white text-sm font-semibold rounded-lg transition"
        >
          <i class="fa-solid" :class="saving ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
          {{ saving ? 'Zapisywanie…' : t('common.save') }}
        </button>
      </div>
    </div>

    <div class="flex gap-4" style="height: calc(100vh - 13rem)">
      <!-- ══ Left: the list of blocks ════════════════════════════════════ -->
      <div class="w-52 shrink-0 flex flex-col gap-1 overflow-y-auto pr-1">
        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider px-1 mb-1">
          {{ t('manager.homepagebuilder.index.page_blocks') }}
        </p>
        <div
          v-for="(block, i) in blocks"
          :key="block.id"
          @click="select(i)"
          :class="[
            'group flex items-center gap-2 px-2.5 py-2 rounded-lg border cursor-pointer transition-all',
            selectedIdx === i ? 'border-blue-500 bg-blue-50' : 'border-gray-200 bg-white hover:border-gray-300',
            !block.enabled && 'opacity-40',
          ]"
        >
          <div class="flex flex-col gap-px shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
            <button
              @click.stop="moveUp(i)"
              :disabled="i === 0"
              class="w-4 h-4 flex items-center justify-center rounded hover:bg-gray-200 disabled:opacity-20 text-gray-500"
            >
              <i class="fa-solid fa-chevron-up text-[9px]"></i>
            </button>
            <button
              @click.stop="moveDown(i)"
              :disabled="i === blocks.length - 1"
              class="w-4 h-4 flex items-center justify-center rounded hover:bg-gray-200 disabled:opacity-20 text-gray-500"
            >
              <i class="fa-solid fa-chevron-down text-[9px]"></i>
            </button>
          </div>
          <div
            class="w-7 h-7 rounded-md flex items-center justify-center shrink-0"
            :class="meta[block.type]?.bg ?? 'bg-gray-100'"
          >
            <i
              :class="[
                meta[block.type]?.icon ?? 'fa-solid fa-cube',
                meta[block.type]?.color ?? 'text-gray-500',
                'text-xs',
              ]"
            ></i>
          </div>
          <span :class="['flex-1 text-xs font-medium truncate', selectedIdx === i ? 'text-blue-700' : 'text-gray-700']">
            {{ meta[block.type]?.label ?? block.type }}
          </span>
          <button
            @click.stop="toggleBlock(i)"
            class="shrink-0 w-7 h-4 rounded-full transition-colors relative"
            :class="block.enabled ? 'bg-blue-500' : 'bg-gray-200'"
          >
            <span
              class="absolute top-0.5 w-3 h-3 bg-white rounded-full shadow transition-all"
              :class="block.enabled ? 'left-3.5' : 'left-0.5'"
            ></span>
          </button>
        </div>
      </div>

      <!-- ══ Middle: the preview ═════════════════════════════════════════ -->
      <div class="flex-1 overflow-y-auto bg-gray-100 rounded-xl border border-gray-200">
        <div
          class="sticky top-0 z-10 bg-gray-200 px-3 py-2 flex items-center gap-2 border-b border-gray-300 rounded-t-xl"
        >
          <div class="flex gap-1.5">
            <span class="w-3 h-3 rounded-full bg-red-400"></span><span class="w-3 h-3 rounded-full bg-yellow-400"></span
            ><span class="w-3 h-3 rounded-full bg-green-400"></span>
          </div>
          <div class="flex-1 bg-white rounded-md px-3 py-1 text-xs text-gray-400 font-mono truncate">{{ shopUrl }}</div>
        </div>

        <div class="bg-white min-h-full" @click="selectedIdx = null">
          <template v-for="block in blocks" :key="block.id">
            <!-- HERO -->
            <div
              v-if="block.type === 'hero' && block.enabled"
              @click.stop="selectByType('hero')"
              :class="previewClass('hero')"
              :style="heroPreviewStyle"
            >
              <div class="absolute inset-0 bg-gradient-to-b from-black/20 via-black/45 to-black/80"></div>
              <div class="relative z-10 flex flex-col items-center justify-center text-center px-6 py-12">
                <h1 class="text-2xl font-extrabold text-white drop-shadow mb-2">
                  {{ bs('hero', 'heading') || shopName }}
                </h1>
                <p class="text-white/80 text-sm mb-5">
                  {{ bs('hero', 'subheading') || t('manager.homepagebuilder.index.discover_our_products') }}
                </p>
                <span
                  class="inline-flex items-center gap-2 bg-white text-gray-900 font-bold text-xs px-5 py-2.5 rounded-xl shadow-lg"
                >
                  {{ bs('hero', 'cta_label') || t('common.browse_the_shop') }}
                  <i class="fa-solid fa-arrow-right text-[10px]"></i>
                </span>
              </div>
              <PLabel :label="t('manager.homepagebuilder.index.hero_slider')" :active="selectedIdx === idxOf('hero')" />
            </div>

            <!-- TRUST BADGES -->
            <div
              v-else-if="block.type === 'trust_badges' && block.enabled"
              @click.stop="selectByType('trust_badges')"
              :class="previewClass('trust_badges')"
              class="border-b border-gray-100"
            >
              <div class="max-w-5xl mx-auto px-6 py-4 grid grid-cols-4 gap-3 relative">
                <div
                  v-for="(b, i) in block.settings.badges ?? defaultBadges"
                  :key="i"
                  class="flex items-center gap-2.5"
                >
                  <div
                    class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                    :style="{ background: b.bg ?? '#f0fdf4' }"
                  >
                    <i :class="[b.icon, 'text-base']" :style="{ color: b.color ?? '#059669' }"></i>
                  </div>
                  <div>
                    <div class="text-xs font-bold text-gray-900">{{ b.label }}</div>
                    <div class="text-[10px] text-gray-400">{{ b.sub }}</div>
                  </div>
                </div>
                <PLabel :label="t('common.trust_bar')" :active="selectedIdx === idxOf('trust_badges')" />
              </div>
            </div>

            <!-- CATEGORIES -->
            <div
              v-else-if="block.type === 'categories' && block.enabled"
              @click.stop="selectByType('categories')"
              :class="previewClass('categories')"
              class="px-6 py-7 relative"
            >
              <SectionHead
                :heading="bs('categories', 'heading') || t('common.categories')"
                :sub="bs('categories', 'subheading') || t('manager.homepagebuilder.index.browse_the_range')"
              />
              <div class="grid grid-cols-4 gap-3 mt-4">
                <div
                  v-for="(c, i) in previewCats"
                  :key="i"
                  class="aspect-[4/3] rounded-xl flex items-end p-3 relative overflow-hidden"
                  :style="{ backgroundColor: catColors[i % catColors.length] }"
                >
                  <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                  <span class="relative z-10 text-white text-xs font-bold truncate">{{ c }}</span>
                </div>
              </div>
              <PLabel :label="t('common.categories')" :active="selectedIdx === idxOf('categories')" />
            </div>

            <!-- PRODUCT SECTIONS -->
            <div
              v-else-if="['featured_products', 'bestsellers', 'new_arrivals'].includes(block.type) && block.enabled"
              @click.stop="selectByType(block.type)"
              :class="[previewClass(block.type), block.type === 'bestsellers' ? 'bg-white' : 'bg-gray-50']"
              class="px-6 py-7 relative"
            >
              <SectionHead
                :heading="bs(block.type, 'heading') || meta[block.type].label"
                :sub="bs(block.type, 'subheading') || meta[block.type].defaultSub"
              />
              <div class="grid grid-cols-4 gap-3 mt-4">
                <div
                  v-for="n in Math.min(parseInt(bs(block.type, 'limit')) || 4, 8)"
                  :key="n"
                  class="rounded-xl overflow-hidden bg-white border border-gray-100"
                >
                  <div class="aspect-square bg-gray-100"></div>
                  <div class="p-2 space-y-1.5">
                    <div class="h-2.5 bg-gray-100 rounded w-3/4"></div>
                    <div class="h-2 bg-gray-100 rounded w-1/2"></div>
                    <div class="h-6 bg-gray-100 rounded mt-1"></div>
                  </div>
                </div>
              </div>
              <PLabel :label="meta[block.type].label" :active="selectedIdx === idxOf(block.type)" />
            </div>

            <!-- BANNER -->
            <div
              v-else-if="block.type === 'banner' && block.enabled"
              @click.stop="selectByType('banner')"
              :class="previewClass('banner')"
              class="relative overflow-hidden"
              :style="{ backgroundColor: bs('banner', 'bg_color') || '#1e40af' }"
            >
              <div class="max-w-2xl mx-auto px-6 py-10 text-center text-white relative z-10">
                <h2 class="text-xl font-extrabold mb-2">
                  {{ bs('banner', 'heading') || t('common.promotional_banner') }}
                </h2>
                <p class="text-white/70 text-sm mb-4">
                  {{ bs('banner', 'text') || t('common.the_promotion_s_description_goes_here') }}
                </p>
                <span
                  v-if="bs('banner', 'btn_label')"
                  class="inline-block bg-white font-bold text-xs px-5 py-2.5 rounded-xl"
                  :style="{ color: bs('banner', 'bg_color') || '#1e40af' }"
                  >{{ bs('banner', 'btn_label') }}</span
                >
              </div>
              <PLabel :label="t('common.banner')" :active="selectedIdx === idxOf('banner')" />
            </div>

            <!-- ABOUT -->
            <div
              v-else-if="block.type === 'about' && block.enabled"
              @click.stop="selectByType('about')"
              :class="previewClass('about')"
              class="px-6 py-8 relative"
            >
              <div
                :class="[
                  'flex gap-6 items-center',
                  bs('about', 'layout') === 'image_left' ? 'flex-row-reverse' : 'flex-row',
                ]"
              >
                <div class="flex-1">
                  <p class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest mb-1">
                    {{ t('manager.homepagebuilder.index.about_us') }}
                  </p>
                  <h2 class="text-lg font-extrabold text-gray-900 mb-2">{{ bs('about', 'heading') || 'O nas' }}</h2>
                  <p class="text-xs text-gray-500 leading-relaxed line-clamp-4">
                    {{ bs('about', 'text') || t('common.your_shop_s_or_company_s') }}
                  </p>
                </div>
                <div
                  class="w-40 h-28 rounded-xl overflow-hidden shrink-0 bg-gray-100 flex items-center justify-center"
                  :style="
                    bs('about', 'image_url')
                      ? {
                          backgroundImage: `url('${bs('about', 'image_url')}')`,
                          backgroundSize: 'cover',
                          backgroundPosition: 'center',
                        }
                      : {}
                  "
                >
                  <i v-if="!bs('about', 'image_url')" class="fa-solid fa-image text-2xl text-gray-300"></i>
                </div>
              </div>
              <PLabel :label="t('manager.homepagebuilder.index.about_us')" :active="selectedIdx === idxOf('about')" />
            </div>

            <!-- GALLERY -->
            <div
              v-else-if="block.type === 'gallery' && block.enabled"
              @click.stop="selectByType('gallery')"
              :class="previewClass('gallery')"
              class="px-6 py-7 relative bg-gray-50"
            >
              <SectionHead :heading="bs('gallery', 'heading') || 'Galeria'" sub="" />
              <div class="grid grid-cols-4 gap-2 mt-4">
                <div
                  v-for="img in previewGalleryImages.length ? previewGalleryImages.slice(0, 8) : [1, 2, 3, 4]"
                  :key="img"
                  class="aspect-square rounded-lg overflow-hidden bg-gray-200 flex items-center justify-center"
                >
                  <img v-if="typeof img === 'string'" :src="img" alt="" class="w-full h-full object-cover" />
                  <i v-else class="fa-solid fa-image text-gray-300 text-xl"></i>
                </div>
              </div>
              <PLabel :label="t('manager.homepagebuilder.index.gallery')" :active="selectedIdx === idxOf('gallery')" />
            </div>

            <!-- REVIEWS -->
            <div
              v-else-if="block.type === 'reviews' && block.enabled"
              @click.stop="selectByType('reviews')"
              :class="previewClass('reviews')"
              class="px-6 py-7 relative bg-white"
            >
              <SectionHead
                :heading="bs('reviews', 'heading') || t('common.customer_reviews')"
                :sub="bs('reviews', 'subheading') || t('manager.homepagebuilder.index.what_our_customers_say')"
              />
              <div class="grid grid-cols-3 gap-3 mt-4">
                <div v-for="n in 3" :key="n" class="border border-gray-100 rounded-xl p-3 space-y-2">
                  <div class="flex gap-0.5">
                    <i v-for="s in 5" :key="s" class="fa-solid fa-star text-yellow-400 text-[10px]"></i>
                  </div>
                  <div class="h-2 bg-gray-100 rounded w-full"></div>
                  <div class="h-2 bg-gray-100 rounded w-3/4"></div>
                  <div class="flex items-center gap-2 mt-2">
                    <div class="w-5 h-5 rounded-full bg-gray-200"></div>
                    <div class="h-2 bg-gray-100 rounded w-16"></div>
                  </div>
                </div>
              </div>
              <PLabel :label="t('common.reviews')" :active="selectedIdx === idxOf('reviews')" />
            </div>

            <!-- NEWSLETTER -->
            <div
              v-else-if="block.type === 'newsletter' && block.enabled"
              @click.stop="selectByType('newsletter')"
              :class="previewClass('newsletter')"
              class="bg-gray-900 relative"
            >
              <div class="max-w-xl mx-auto px-6 py-10 text-center">
                <h2 class="text-lg font-bold text-white mb-1">
                  {{ bs('newsletter', 'heading') || t('manager.homepagebuilder.index.keep_up_to_date') }}
                </h2>
                <p class="text-gray-400 text-xs mb-4">{{ bs('newsletter', 'text') }}</p>
                <div class="flex gap-2 max-w-xs mx-auto">
                  <div class="flex-1 h-9 rounded-lg bg-gray-800 border border-gray-700"></div>
                  <div class="h-9 w-24 rounded-lg bg-indigo-600"></div>
                </div>
              </div>
              <PLabel label="Newsletter" :active="selectedIdx === idxOf('newsletter')" />
            </div>

            <!-- TESTIMONIALS -->
            <div
              v-else-if="block.type === 'testimonials' && block.enabled"
              @click.stop="selectByType('testimonials')"
              :class="previewClass('testimonials')"
              class="px-6 py-7 relative bg-white"
            >
              <SectionHead
                :heading="bs('testimonials', 'heading') || t('common.customer_reviews')"
                :sub="
                  bs('testimonials', 'subheading') || t('manager.homepagebuilder.index.what_customers_say_about_us')
                "
              />
              <div class="grid grid-cols-3 gap-3 mt-4">
                <div v-for="n in [1, 2, 3]" :key="n" class="border border-gray-100 rounded-xl p-3 space-y-2 bg-gray-50">
                  <div class="flex gap-0.5">
                    <i v-for="s in 5" :key="s" class="fa-solid fa-star text-yellow-400 text-[10px]"></i>
                  </div>
                  <p class="text-[10px] text-gray-500 line-clamp-3">
                    {{
                      bs('testimonials', `item${n}_text`) || t('manager.homepagebuilder.index.review_text_placeholder')
                    }}
                  </p>
                  <p class="text-[10px] font-bold text-gray-700">
                    {{ bs('testimonials', `item${n}_author`) || `Klient ${n}` }}
                  </p>
                </div>
              </div>
              <PLabel :label="t('common.reviews_testimonials')" :active="selectedIdx === idxOf('testimonials')" />
            </div>

            <!-- FAQ -->
            <div
              v-else-if="block.type === 'faq' && block.enabled"
              @click.stop="selectByType('faq')"
              :class="previewClass('faq')"
              class="px-6 py-7 relative bg-gray-50"
            >
              <SectionHead
                :heading="bs('faq', 'heading') || t('common.frequently_asked_questions')"
                :sub="bs('faq', 'subheading') || 'FAQ'"
              />
              <div class="mt-4 divide-y divide-gray-200 bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div
                  v-for="n in [1, 2, 3, 4]"
                  :key="n"
                  v-show="bs('faq', `q${n}`)"
                  class="px-3 py-2.5 flex items-center justify-between gap-2"
                >
                  <span class="text-xs font-semibold text-gray-800 truncate">{{ bs('faq', `q${n}`) }}</span>
                  <i class="fa-solid fa-chevron-down text-gray-300 text-[10px]"></i>
                </div>
              </div>
              <PLabel label="FAQ" :active="selectedIdx === idxOf('faq')" />
            </div>
          </template>

          <div
            v-if="blocks.every((b) => !b.enabled)"
            class="flex flex-col items-center justify-center py-24 text-gray-400"
          >
            <i class="fa-solid fa-eye-slash text-4xl mb-3"></i>
            <p class="text-sm">{{ t('manager.homepagebuilder.index.every_block_is_switched_off') }}</p>
          </div>
        </div>
      </div>

      <!-- ══ Right: the settings panel ═══════════════════════════════════ -->
      <div class="w-80 shrink-0 overflow-y-auto">
        <div
          v-if="selectedIdx === null"
          class="bg-white border border-dashed border-gray-200 rounded-xl p-8 text-center"
        >
          <i class="fa-solid fa-hand-pointer text-3xl text-gray-300 mb-3"></i>
          <p class="text-sm text-gray-500 font-medium">
            {{ t('manager.homepagebuilder.index.click_a_block_in_the_preview') }}
          </p>
          <p class="text-xs text-gray-400 mt-1">{{ t('manager.homepagebuilder.index.or_pick_one_from_the_list') }}</p>
        </div>

        <div v-else class="bg-white border border-gray-200 rounded-xl overflow-hidden">
          <!-- Panel header -->
          <div class="px-4 py-3 border-b border-gray-100 flex items-center gap-2.5 bg-gray-50">
            <div
              class="w-8 h-8 rounded-lg flex items-center justify-center"
              :class="meta[current.type]?.bg ?? 'bg-gray-100'"
            >
              <i
                :class="[
                  meta[current.type]?.icon ?? 'fa-solid fa-cube',
                  meta[current.type]?.color ?? 'text-gray-500',
                  'text-sm',
                ]"
              ></i>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-semibold text-gray-900">{{ meta[current.type]?.label }}</p>
              <p class="text-xs text-gray-400 truncate">{{ meta[current.type]?.desc }}</p>
            </div>
          </div>

          <div class="p-4 space-y-4">
            <!-- Visibility, shown for every block -->
            <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
              <span class="text-sm text-gray-700 font-medium">{{
                t('manager.homepagebuilder.index.shown_on_the_page')
              }}</span>
              <Toggle :model-value="current.enabled" @update:model-value="toggleBlock(selectedIdx)" />
            </div>

            <!-- ── HERO ─────────────────────────────────────────────── -->
            <template v-if="current.type === 'hero'">
              <!-- Slider images file manager -->
              <div class="space-y-2">
                <label class="block text-xs font-medium text-gray-600">{{
                  t('manager.homepagebuilder.index.slider_images')
                }}</label>
                <div class="grid grid-cols-3 gap-1.5">
                  <div
                    v-for="(url, i) in heroSlideUrls"
                    :key="i"
                    class="aspect-video rounded-lg overflow-hidden bg-gray-100 relative group"
                  >
                    <img :src="url" alt="" class="w-full h-full object-cover" />
                    <div
                      class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100"
                    >
                      <button
                        type="button"
                        @click="moveHeroSlide(i, -1)"
                        :disabled="i === 0"
                        class="w-5 h-5 rounded bg-white/80 text-gray-700 text-[9px] flex items-center justify-center disabled:opacity-30"
                      >
                        <i class="fa-solid fa-chevron-left"></i>
                      </button>
                      <button
                        type="button"
                        @click="removeHeroSlide(i)"
                        class="w-5 h-5 rounded bg-red-500 text-white text-[9px] flex items-center justify-center"
                      >
                        <i class="fa-solid fa-xmark"></i>
                      </button>
                      <button
                        type="button"
                        @click="moveHeroSlide(i, 1)"
                        :disabled="i === heroSlideUrls.length - 1"
                        class="w-5 h-5 rounded bg-white/80 text-gray-700 text-[9px] flex items-center justify-center disabled:opacity-30"
                      >
                        <i class="fa-solid fa-chevron-right"></i>
                      </button>
                    </div>
                    <div class="absolute bottom-0.5 left-0 right-0 text-center text-[8px] text-white/70 font-bold">
                      {{ i + 1 }}
                    </div>
                  </div>
                  <!-- Upload button -->
                  <label
                    :class="[
                      'aspect-video rounded-lg border-2 border-dashed border-gray-300 flex flex-col items-center justify-center cursor-pointer hover:border-blue-400 hover:bg-blue-50 transition-colors',
                      heroUploading && 'opacity-50 pointer-events-none',
                    ]"
                  >
                    <i v-if="!heroUploading" class="fa-solid fa-plus text-gray-400 text-base mb-0.5"></i>
                    <i v-else class="fa-solid fa-spinner fa-spin text-blue-500 text-base mb-0.5"></i>
                    <span class="text-[9px] text-gray-400">{{ heroUploading ? 'Wgrywanie…' : t('common.add') }}</span>
                    <input
                      type="file"
                      accept="image/*"
                      class="hidden"
                      @change="uploadHeroSlide"
                      :disabled="heroUploading"
                    />
                  </label>
                </div>
                <p class="text-[10px] text-gray-400">
                  {{ t('manager.homepagebuilder.index.click_add_to_upload_a_photo') }}
                </p>
              </div>
              <div class="border-t border-gray-100 pt-3 space-y-3">
                <SF :label="t('common.heading')">
                  <input
                    v-model="current.settings.heading"
                    type="text"
                    :placeholder="t('common.shop_name')"
                    class="si"
                  />
                </SF>
                <SF :label="t('common.subheading')">
                  <input
                    v-model="current.settings.subheading"
                    type="text"
                    :placeholder="t('manager.homepagebuilder.index.discover_our_products')"
                    class="si"
                  />
                </SF>
                <SF :label="t('common.call_to_action_button_text')">
                  <input
                    v-model="current.settings.cta_label"
                    type="text"
                    :placeholder="t('common.browse_the_shop')"
                    class="si"
                  />
                </SF>
                <SF :label="t('common.slider_height')">
                  <select v-model="current.settings.height" class="si">
                    <option value="sm">{{ t('manager.homepagebuilder.index.small_380px') }}</option>
                    <option value="md">{{ t('manager.homepagebuilder.index.medium_480px') }}</option>
                    <option value="lg">{{ t('manager.homepagebuilder.index.large_600px') }}</option>
                    <option value="full">{{ t('manager.homepagebuilder.index.full_screen') }}</option>
                  </select>
                </SF>
              </div>
            </template>

            <!-- ── TRUST BADGES ──────────────────────────────────────── -->
            <template v-else-if="current.type === 'trust_badges'">
              <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">4 ikony zaufania</p>
              <div
                v-for="(badge, bi) in currentBadges"
                :key="bi"
                class="border border-gray-200 rounded-xl p-3 space-y-2.5"
              >
                <div class="flex items-center gap-2 mb-1">
                  <div class="w-7 h-7 rounded-lg flex items-center justify-center" :style="{ background: badge.bg }">
                    <i :class="[badge.icon, 'text-sm']" :style="{ color: badge.color }"></i>
                  </div>
                  <span class="text-xs font-bold text-gray-700">Ikona {{ bi + 1 }}</span>
                </div>
                <SF :label="t('manager.homepagebuilder.index.icon_font_awesome')">
                  <div class="flex gap-2">
                    <input v-model="badge.icon" type="text" placeholder="fa-solid fa-truck-fast" class="si flex-1" />
                    <div
                      class="w-9 h-9 rounded-lg border border-gray-200 flex items-center justify-center shrink-0"
                      :style="{ background: badge.bg }"
                    >
                      <i :class="[badge.icon, 'text-base']" :style="{ color: badge.color }"></i>
                    </div>
                  </div>
                  <p class="text-[10px] text-gray-400 mt-1">
                    {{ t('manager.homepagebuilder.index.e_g_fa_solid_fa_truck') }}
                  </p>
                </SF>
                <SF :label="t('common.title')"
                  ><input v-model="badge.label" type="text" :placeholder="t('common.free_delivery')" class="si"
                /></SF>
                <SF :label="t('common.subheading')"
                  ><input
                    v-model="badge.sub"
                    type="text"
                    :placeholder="t('manager.homepagebuilder.index.over_199_pln')"
                    class="si"
                /></SF>
                <div class="grid grid-cols-2 gap-2">
                  <SF :label="t('common.icon_colour')">
                    <div class="flex gap-1.5">
                      <input
                        v-model="badge.color"
                        type="color"
                        class="w-9 h-9 rounded border border-gray-200 p-0.5 cursor-pointer"
                      />
                      <input v-model="badge.color" type="text" class="si flex-1 min-w-0" />
                    </div>
                  </SF>
                  <SF :label="t('common.background_colour')">
                    <div class="flex gap-1.5">
                      <input
                        v-model="badge.bg"
                        type="color"
                        class="w-9 h-9 rounded border border-gray-200 p-0.5 cursor-pointer"
                      />
                      <input v-model="badge.bg" type="text" class="si flex-1 min-w-0" />
                    </div>
                  </SF>
                </div>
              </div>
            </template>

            <!-- ── CATEGORIES ────────────────────────────────────────── -->
            <template v-else-if="current.type === 'categories'">
              <SF :label="t('common.section_heading')"
                ><input v-model="current.settings.heading" type="text" :placeholder="t('common.categories')" class="si"
              /></SF>
              <SF :label="t('common.subheading')"
                ><input
                  v-model="current.settings.subheading"
                  type="text"
                  :placeholder="t('manager.homepagebuilder.index.browse_the_range')"
                  class="si"
              /></SF>
              <SF :label="t('common.how_many_categories_to_show')">
                <select v-model="current.settings.limit" class="si">
                  <option value="4">4</option>
                  <option value="6">6</option>
                  <option value="8">{{ t('manager.homepagebuilder.index.8_default') }}</option>
                  <option value="12">12</option>
                </select>
              </SF>
              <SF :label="t('common.top_level_categories_only')">
                <label class="flex items-center gap-2 cursor-pointer">
                  <input type="checkbox" v-model="current.settings.only_roots" class="w-4 h-4 text-blue-600 rounded" />
                  <span class="text-sm text-gray-700">{{
                    t('manager.homepagebuilder.index.yes_show_top_level_categories_only')
                  }}</span>
                </label>
              </SF>
            </template>

            <!-- ── PRODUCT SECTIONS (featured/bestsellers/new_arrivals) ─ -->
            <template v-else-if="['featured_products', 'bestsellers', 'new_arrivals'].includes(current.type)">
              <SF :label="t('common.section_heading')"
                ><input
                  v-model="current.settings.heading"
                  type="text"
                  :placeholder="meta[current.type]?.label"
                  class="si"
              /></SF>
              <SF :label="t('common.subheading')"
                ><input v-model="current.settings.subheading" type="text" class="si"
              /></SF>
              <SF :label="t('common.product_source')">
                <select v-model="current.settings.source" class="si">
                  <option value="featured">{{ t('manager.homepagebuilder.index.featured_flagged') }}</option>
                  <option value="bestsellers">{{ t('manager.homepagebuilder.index.bestsellers_by_sales') }}</option>
                  <option value="newest">{{ t('manager.homepagebuilder.index.newest_date_added') }}</option>
                  <option value="sale">
                    {{ t('manager.homepagebuilder.index.sale_products_with_a_reduced_price') }}
                  </option>
                  <option value="category">{{ t('manager.homepagebuilder.index.from_the_chosen_category') }}</option>
                  <option value="random">{{ t('manager.homepagebuilder.index.random') }}</option>
                </select>
              </SF>
              <SF v-if="current.settings.source === 'category'" :label="t('common.choose_a_category')">
                <select v-model="current.settings.category_id" class="si">
                  <option value="">{{ t('manager.homepagebuilder.index.choose') }}</option>
                  <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                </select>
              </SF>
              <SF :label="t('common.how_many_products')">
                <select v-model="current.settings.limit" class="si">
                  <option value="4">4</option>
                  <option value="8">8</option>
                  <option value="12">12</option>
                </select>
              </SF>
              <SF :label="t('manager.homepagebuilder.index.columns')">
                <select v-model="current.settings.columns" class="si">
                  <option value="">{{ t('manager.homepagebuilder.index.auto_4') }}</option>
                  <option value="2">2</option>
                  <option value="3">3</option>
                  <option value="4">4</option>
                  <option value="5">5</option>
                </select>
              </SF>
              <SF :label="t('common.display_options')">
                <div class="space-y-2">
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" v-model="current.settings.show_link" class="w-4 h-4 text-blue-600 rounded" />
                    <span class="text-sm text-gray-700">{{
                      t('manager.homepagebuilder.index.show_the_see_all_link')
                    }}</span>
                  </label>
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input
                      type="checkbox"
                      v-model="current.settings.show_badges"
                      class="w-4 h-4 text-blue-600 rounded"
                    />
                    <span class="text-sm text-gray-700">{{
                      t('manager.homepagebuilder.index.show_the_new_sale_badge')
                    }}</span>
                  </label>
                </div>
              </SF>
            </template>

            <!-- ── BANNER ────────────────────────────────────────────── -->
            <template v-else-if="current.type === 'banner'">
              <SF :label="t('common.label_above_the_heading_optional')"
                ><input
                  v-model="current.settings.eyebrow"
                  type="text"
                  :placeholder="t('manager.homepagebuilder.index.big_promotion')"
                  class="si"
              /></SF>
              <SF :label="t('common.heading')"
                ><input
                  v-model="current.settings.heading"
                  type="text"
                  :placeholder="t('manager.homepagebuilder.index.big_sale')"
                  class="si"
              /></SF>
              <SF :label="t('common.description')">
                <textarea v-model="current.settings.text" rows="2" class="si resize-none"></textarea>
              </SF>
              <SF :label="t('common.button_text')"
                ><input
                  v-model="current.settings.btn_label"
                  type="text"
                  :placeholder="t('manager.homepagebuilder.index.see_what_we_sell')"
                  class="si"
              /></SF>
              <SF :label="t('common.button_link')"
                ><input
                  v-model="current.settings.btn_url"
                  type="text"
                  :placeholder="t('manager.homepagebuilder.index.shop')"
                  class="si"
              /></SF>
              <SF :label="t('common.background_colour')">
                <div class="flex gap-2">
                  <input
                    v-model="current.settings.bg_color"
                    type="color"
                    class="w-9 h-9 rounded border border-gray-200 p-0.5 cursor-pointer"
                  />
                  <input v-model="current.settings.bg_color" type="text" placeholder="#1e40af" class="si flex-1" />
                </div>
              </SF>
              <SF :label="t('manager.homepagebuilder.index.image_optional')">
                <input v-model="current.settings.image_url" type="text" placeholder="https://…" class="si" />
                <p class="text-[10px] text-gray-400 mt-1">
                  {{ t('manager.homepagebuilder.index.when_set_the_banner_shows_text') }}
                </p>
              </SF>
              <SF :label="t('manager.homepagebuilder.index.countdown_optional')">
                <input v-model="current.settings.countdown_end" type="datetime-local" class="si" />
                <p class="text-[10px] text-gray-400 mt-1">
                  {{ t('manager.homepagebuilder.index.set_a_date_and_time_and') }}
                </p>
              </SF>
            </template>

            <!-- ── NEWSLETTER ────────────────────────────────────────── -->
            <template v-else-if="current.type === 'newsletter'">
              <SF :label="t('common.heading')"
                ><input
                  v-model="current.settings.heading"
                  type="text"
                  :placeholder="t('manager.homepagebuilder.index.keep_up_to_date')"
                  class="si"
              /></SF>
              <SF :label="t('common.description')"
                ><input
                  v-model="current.settings.text"
                  type="text"
                  :placeholder="t('manager.homepagebuilder.index.subscribe_and_get_our_offers')"
                  class="si"
              /></SF>
              <SF :label="t('common.button_text')"
                ><input
                  v-model="current.settings.btn_label"
                  type="text"
                  :placeholder="t('common.subscribe')"
                  class="si"
              /></SF>
              <SF :label="t('manager.homepagebuilder.index.style')">
                <select v-model="current.settings.style" class="si">
                  <option value="dark">{{ t('manager.homepagebuilder.index.dark_default') }}</option>
                  <option value="light">{{ t('manager.homepagebuilder.index.light') }}</option>
                  <option value="primary">{{ t('manager.homepagebuilder.index.coloured_accent_colour') }}</option>
                </select>
              </SF>
            </template>

            <!-- ── O NAS ─────────────────────────────────────────────── -->
            <template v-else-if="current.type === 'about'">
              <SF :label="t('common.heading')"
                ><input
                  v-model="current.settings.heading"
                  type="text"
                  :placeholder="t('manager.homepagebuilder.index.about_us')"
                  class="si"
              /></SF>
              <SF :label="t('common.content_2')">
                <textarea
                  v-model="current.settings.text"
                  rows="5"
                  :placeholder="t('manager.homepagebuilder.index.tell_your_shop_s_story')"
                  class="si resize-none"
                ></textarea>
              </SF>
              <SF :label="t('common.photo_url')">
                <input v-model="current.settings.image_url" type="text" placeholder="https://…" class="si" />
                <div v-if="current.settings.image_url" class="mt-2 rounded-lg overflow-hidden h-24 bg-gray-100">
                  <img :src="current.settings.image_url" alt="" class="w-full h-full object-cover" />
                </div>
              </SF>
              <SF :label="t('common.layout')">
                <select v-model="current.settings.layout" class="si">
                  <option value="image_right">{{ t('manager.homepagebuilder.index.photo_on_the_right') }}</option>
                  <option value="image_left">{{ t('manager.homepagebuilder.index.photo_on_the_left') }}</option>
                </select>
              </SF>
            </template>

            <!-- ── GALERIA ───────────────────────────────────────────── -->
            <template v-else-if="current.type === 'gallery'">
              <SF :label="t('common.section_heading')"
                ><input
                  v-model="current.settings.heading"
                  type="text"
                  :placeholder="t('manager.homepagebuilder.index.gallery')"
                  class="si"
              /></SF>
              <SF :label="t('common.photos_one_url_per_line')">
                <textarea
                  v-model="current.settings.images"
                  rows="8"
                  placeholder="https://example.com/foto1.jpg&#10;https://example.com/foto2.jpg&#10;…"
                  class="si resize-none font-mono text-[11px]"
                ></textarea>
                <p class="text-[10px] text-gray-400 mt-1">
                  {{ t('manager.homepagebuilder.index.paste_one_image_url_per_line') }}
                  <a :href="route('tenant.manager.settings.index')" class="text-blue-600 underline">{{
                    t('manager.homepagebuilder.index.settings_appearance')
                  }}</a
                  >.
                </p>
              </SF>
              <SF :label="t('manager.homepagebuilder.index.columns')">
                <select v-model="current.settings.columns" class="si">
                  <option value="3">3</option>
                  <option value="4">{{ t('manager.homepagebuilder.index.4_default') }}</option>
                  <option value="5">5</option>
                </select>
              </SF>
            </template>

            <!-- ── OPINIE ────────────────────────────────────────────── -->
            <template v-else-if="current.type === 'reviews'">
              <SF :label="t('common.heading')"
                ><input
                  v-model="current.settings.heading"
                  type="text"
                  :placeholder="t('common.customer_reviews')"
                  class="si"
              /></SF>
              <SF :label="t('common.subheading')"
                ><input
                  v-model="current.settings.subheading"
                  type="text"
                  :placeholder="t('manager.homepagebuilder.index.what_our_customers_say')"
                  class="si"
              /></SF>
              <SF :label="t('manager.homepagebuilder.index.number_of_reviews')">
                <select v-model="current.settings.limit" class="si">
                  <option value="3">3</option>
                  <option value="6">{{ t('manager.homepagebuilder.index.6_default') }}</option>
                  <option value="9">9</option>
                  <option value="12">12</option>
                </select>
              </SF>
              <div class="text-xs text-blue-700 bg-blue-50 rounded-lg p-3">
                <i class="fa-solid fa-star mr-1"></i>
                {{ t('manager.homepagebuilder.index.shows_reviews_from') }}
                <a :href="route('tenant.manager.reviews.index')" class="underline font-medium">{{
                  t('common.reviews')
                }}</a>
                {{ t('manager.homepagebuilder.index.marked_as_featured') }}
              </div>
            </template>

            <!-- ── TESTIMONIALS ──────────────────────────────────────── -->
            <template v-else-if="current.type === 'testimonials'">
              <SF :label="t('common.heading')"
                ><input
                  v-model="current.settings.heading"
                  type="text"
                  :placeholder="t('common.customer_reviews')"
                  class="si"
              /></SF>
              <SF :label="t('common.subheading')"
                ><input
                  v-model="current.settings.subheading"
                  type="text"
                  :placeholder="t('manager.homepagebuilder.index.what_customers_say_about_us')"
                  class="si"
              /></SF>
              <div v-for="n in [1, 2, 3]" :key="n" class="border border-gray-200 rounded-xl p-3 space-y-2.5">
                <p class="text-xs font-bold text-gray-700">Opinia {{ n }}</p>
                <SF :label="t('common.review_text')">
                  <textarea
                    v-model="current.settings[`item${n}_text`]"
                    rows="2"
                    :placeholder="t('manager.homepagebuilder.index.great_service_and_fast_delivery')"
                    class="si resize-none"
                  ></textarea>
                </SF>
                <SF :label="t('landlord.modifications.form.author')"
                  ><input v-model="current.settings[`item${n}_author`]" type="text" placeholder="Anna K." class="si"
                /></SF>
                <SF :label="t('manager.homepagebuilder.index.rating_1_5')">
                  <select v-model="current.settings[`item${n}_rating`]" class="si">
                    <option v-for="r in [5, 4, 3, 2, 1]" :key="r" :value="String(r)">{{ r }}</option>
                  </select>
                </SF>
              </div>
            </template>

            <!-- ── FAQ ───────────────────────────────────────────────── -->
            <template v-else-if="current.type === 'faq'">
              <SF :label="t('common.heading')"
                ><input
                  v-model="current.settings.heading"
                  type="text"
                  :placeholder="t('common.frequently_asked_questions')"
                  class="si"
              /></SF>
              <SF :label="t('common.subheading')"
                ><input v-model="current.settings.subheading" type="text" placeholder="FAQ" class="si"
              /></SF>
              <div v-for="n in [1, 2, 3, 4]" :key="n" class="border border-gray-200 rounded-xl p-3 space-y-2.5">
                <p class="text-xs font-bold text-gray-700">Pytanie {{ n }}</p>
                <SF :label="t('manager.homepagebuilder.index.question')"
                  ><input
                    v-model="current.settings[`q${n}`]"
                    type="text"
                    :placeholder="t('manager.homepagebuilder.index.how_long_does_delivery_take')"
                    class="si"
                /></SF>
                <SF :label="t('landlord.support.show.reply')">
                  <textarea
                    v-model="current.settings[`a${n}`]"
                    rows="2"
                    :placeholder="t('manager.homepagebuilder.index.usually_1_2_working_days')"
                    class="si resize-none"
                  ></textarea>
                </SF>
              </div>
            </template>
          </div>
        </div>
      </div>
    </div>
  </ManagerLayout>
</template>

<script setup>
import { ref, computed, watch, h, defineComponent } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import ManagerLayout from '@/Layouts/ManagerLayout.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps({
  blocks: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
})

const page = usePage()
const shopName = computed(() => page.props.tenant?.name ?? t('common.shop'))
const shopUrl = computed(() => {
  try {
    return route('tenant.shop')
  } catch {
    return '/'
  }
})

const blocks = ref(props.blocks.map((b) => ({ ...b, settings: { ...(b.settings ?? {}) } })))
const selectedIdx = ref(null)
const saving = ref(false)
const categories = props.categories

const current = computed(() => (selectedIdx.value !== null ? blocks.value[selectedIdx.value] : null))

// Fill the hero's defaults in the first time it is opened.
function initHeroDefaults() {
  const hero = blocks.value.find((b) => b.type === 'hero')
  if (!hero) return
  const s = hero.settings
  if (!s.heading) s.heading = page.props.tenant?.name ?? ''
  if (!s.subheading) s.subheading = 'Odkryj nasze produkty'
  if (!s.cta_label) s.cta_label = t('manager.homepagebuilder.index.browse_the_shop_default')
}
initHeroDefaults()

// Badges z ustawieniami bloku trust_badges lub defaults
const defaultBadges = [
  {
    icon: 'fa-solid fa-truck-fast',
    label: t('common.free_delivery'),
    sub: t('manager.homepagebuilder.index.over_199_pln'),
    color: '#059669',
    bg: '#ecfdf5',
  },
  {
    icon: 'fa-solid fa-shield-halved',
    label: t('common.secure_payments'),
    sub: t('client.shop.index.ssl_3d_secure'),
    color: '#2563eb',
    bg: '#eff6ff',
  },
  {
    icon: 'fa-solid fa-rotate-left',
    label: t('common.30_days_to_return'),
    sub: t('common.no_reason_needed'),
    color: '#7c3aed',
    bg: '#f5f3ff',
  },
  {
    icon: 'fa-solid fa-headset',
    label: t('common.customer_support'),
    sub: t('common.mon_fri_9_5'),
    color: '#d97706',
    bg: '#fffbeb',
  },
]

// Filling in the defaults is a side effect, and a computed property that
// writes to what it reads re-triggers itself. Watching the selection does the
// same job at the moment it actually changes.
watch(
  current,
  (block) => {
    if (block?.type === 'trust_badges' && !block.settings.badges) {
      block.settings.badges = defaultBadges.map((badge) => ({ ...badge }))
    }
  },
  { immediate: true },
)

const currentBadges = computed(() =>
  current.value?.type === 'trust_badges' ? (current.value.settings.badges ?? []) : [],
)

const meta = {
  hero: {
    label: t('manager.homepagebuilder.index.hero_slider'),
    desc: t('common.a_slider_with_images_and_a'),
    icon: 'fa-solid fa-images',
    color: 'text-indigo-600',
    bg: 'bg-indigo-50',
    defaultSub: '',
  },
  trust_badges: {
    label: t('common.trust_bar'),
    desc: t('common.4_icons_and_text'),
    icon: 'fa-solid fa-shield-halved',
    color: 'text-green-600',
    bg: 'bg-green-50',
  },
  categories: {
    label: t('common.categories'),
    desc: t('common.a_grid_of_product_categories'),
    icon: 'fa-solid fa-folder-tree',
    color: 'text-amber-600',
    bg: 'bg-amber-50',
  },
  featured_products: {
    label: t('common.featured'),
    desc: t('common.selected_products'),
    icon: 'fa-solid fa-star',
    color: 'text-yellow-500',
    bg: 'bg-yellow-50',
    defaultSub: t('common.picked_for_you'),
  },
  bestsellers: {
    label: t('client.shop.category.bestsellers'),
    desc: t('common.most_bought'),
    icon: 'fa-solid fa-fire',
    color: 'text-orange-500',
    bg: 'bg-orange-50',
    defaultSub: t('common.most_popular'),
  },
  new_arrivals: {
    label: t('common.new_in'),
    desc: t('common.recently_added'),
    icon: 'fa-solid fa-wand-magic-sparkles',
    color: 'text-blue-500',
    bg: 'bg-blue-50',
    defaultSub: t('common.just_added'),
  },
  banner: {
    label: t('common.banner'),
    desc: t('common.your_own_banner_with_a_call'),
    icon: 'fa-solid fa-rectangle-ad',
    color: 'text-pink-500',
    bg: 'bg-pink-50',
  },
  about: {
    label: t('manager.homepagebuilder.index.about_us'),
    desc: t('common.a_section_telling_the_company_s'),
    icon: 'fa-solid fa-circle-info',
    color: 'text-sky-600',
    bg: 'bg-sky-50',
  },
  gallery: {
    label: t('manager.homepagebuilder.index.gallery'),
    desc: t('common.a_grid_of_photos'),
    icon: 'fa-solid fa-images',
    color: 'text-teal-600',
    bg: 'bg-teal-50',
  },
  reviews: {
    label: t('common.reviews'),
    desc: t('common.customer_reviews'),
    icon: 'fa-solid fa-star',
    color: 'text-yellow-500',
    bg: 'bg-yellow-50',
  },
  newsletter: {
    label: 'Newsletter',
    desc: t('manager.homepagebuilder.index.email_sign_up_form'),
    icon: 'fa-solid fa-envelope',
    color: 'text-violet-500',
    bg: 'bg-violet-50',
  },
  testimonials: {
    label: t('manager.homepagebuilder.index.testimonials'),
    desc: t('common.quotes_from_customer_reviews'),
    icon: 'fa-solid fa-quote-left',
    color: 'text-rose-500',
    bg: 'bg-rose-50',
  },
  faq: {
    label: 'FAQ',
    desc: t('common.frequently_asked_questions_accordion'),
    icon: 'fa-solid fa-circle-question',
    color: 'text-cyan-600',
    bg: 'bg-cyan-50',
  },
}

const catColors = ['#eef2ff', '#fdf2f8', '#fffbeb', '#ecfdf5', '#eff6ff', '#f5f3ff', '#fef2f2', '#ecfeff']
const previewCats = computed(() =>
  categories.length ? categories.slice(0, 4).map((c) => c.name) : ['Elektronika', 'Ubrania', 'Sport', 'Dom'],
)
const previewGalleryImages = computed(() => {
  const b = blocks.value.find((b) => b.type === 'gallery')
  const raw = b?.settings?.images ?? ''
  return raw
    .split('\n')
    .map((s) => s.trim())
    .filter(Boolean)
})

const heroUploading = ref(false)

const heroSlideUrls = computed(() => {
  const b = blocks.value.find((b) => b.type === 'hero')
  const raw = b?.settings?.slides ?? ''
  return raw
    .split('\n')
    .map((s) => s.trim())
    .filter(Boolean)
})

function heroBlock() {
  return blocks.value.find((b) => b.type === 'hero')
}

function setHeroSlides(urls) {
  const b = heroBlock()
  if (b) b.settings.slides = urls.join('\n')
}

function removeHeroSlide(idx) {
  const lines = [...heroSlideUrls.value]
  lines.splice(idx, 1)
  setHeroSlides(lines)
}

function moveHeroSlide(idx, dir) {
  const lines = [...heroSlideUrls.value]
  const target = idx + dir
  if (target < 0 || target >= lines.length) return
  ;[lines[idx], lines[target]] = [lines[target], lines[idx]]
  setHeroSlides(lines)
}

async function uploadHeroSlide(event) {
  const file = event.target.files[0]
  if (!file) return
  heroUploading.value = true
  const data = new FormData()
  data.append('file', file)
  data.append('field', 'hero_slide')
  try {
    const res = await axios.post(route('tenant.manager.settings.upload'), data)
    setHeroSlides([...heroSlideUrls.value, res.data.url])
  } finally {
    heroUploading.value = false
    event.target.value = ''
  }
}

const heroPreviewStyle = computed(() => {
  const h = bs('hero', 'height')
  const heights = { sm: '180px', md: '240px', lg: '320px', full: '400px' }
  const slidesRaw = bs('hero', 'slides')
  const firstSlide = slidesRaw
    ? slidesRaw
        .split('\n')
        .map((s) => s.trim())
        .find(Boolean)
    : null
  const bgUrl = firstSlide || page.props.tenant?.hero_image_url
  return {
    minHeight: heights[h] ?? '240px',
    backgroundImage: bgUrl ? `url('${bgUrl}')` : 'linear-gradient(135deg,#1e3a8a,#7c3aed)',
    backgroundSize: 'cover',
    backgroundPosition: 'center',
  }
})

function idxOf(type) {
  return blocks.value.findIndex((b) => b.type === type)
}
function bs(type, key) {
  const b = blocks.value.find((b) => b.type === type)
  return b?.settings?.[key] ?? ''
}
function select(i) {
  selectedIdx.value = i
}
function selectByType(t) {
  const i = idxOf(t)
  if (i !== -1) selectedIdx.value = i
}

function previewClass(type) {
  const base = 'cursor-pointer transition-all relative'
  return selectedIdx.value === idxOf(type)
    ? base + ' ring-2 ring-blue-500 ring-inset'
    : base + ' hover:ring-2 hover:ring-blue-300 hover:ring-inset'
}

function toggleBlock(i) {
  const arr = blocks.value
  const wasEnabled = arr[i].enabled
  arr[i].enabled = !wasEnabled

  if (!wasEnabled) {
    // The last active block, not counting the one just switched on.
    let lastActive = -1
    for (let j = 0; j < arr.length; j++) {
      if (j !== i && arr[j].enabled) lastActive = j
    }
    // A newly active block sitting past the active ones moves up behind the last of them.
    if (lastActive !== -1 && i > lastActive + 1) {
      const [moved] = arr.splice(i, 1)
      arr.splice(lastActive + 1, 0, moved)
      selectedIdx.value = lastActive + 1
      return
    }
    selectedIdx.value = i
  }
}

function moveUp(i) {
  if (i === 0) return
  const a = [...blocks.value]
  ;[a[i - 1], a[i]] = [a[i], a[i - 1]]
  blocks.value = a
  selectedIdx.value = i - 1
}
function moveDown(i) {
  if (i === blocks.value.length - 1) return
  const a = [...blocks.value]
  ;[a[i], a[i + 1]] = [a[i + 1], a[i]]
  blocks.value = a
  selectedIdx.value = i + 1
}

function save() {
  saving.value = true
  router.post(
    route('tenant.manager.homepage-builder.save'),
    { blocks: blocks.value },
    {
      preserveScroll: true,
      onFinish: () => {
        saving.value = false
      },
    },
  )
}

// ── Sub-components ────────────────────────────────────────────────────────────
const PLabel = defineComponent({
  props: { label: String, active: Boolean },
  setup(p) {
    return () =>
      h(
        'div',
        {
          class: `absolute top-2 left-2 text-[10px] font-bold px-2 py-0.5 rounded-full z-20 pointer-events-none ${p.active ? 'bg-blue-500 text-white' : 'bg-black/40 text-white/80'}`,
        },
        p.label,
      )
  },
})

const SectionHead = defineComponent({
  props: { heading: String, sub: String },
  setup(p) {
    return () =>
      h('div', {}, [
        h('p', { class: 'text-[10px] font-bold text-indigo-500 uppercase tracking-widest mb-0.5' }, p.sub),
        h('h2', { class: 'text-lg font-extrabold text-gray-900' }, p.heading),
      ])
  },
})

const SF = defineComponent({
  props: { label: String },
  setup(p, { slots }) {
    return () =>
      h('div', { class: 'space-y-1' }, [
        h('label', { class: 'block text-xs font-medium text-gray-600' }, p.label),
        slots.default?.(),
      ])
  },
})

const Toggle = defineComponent({
  props: { modelValue: Boolean },
  emits: ['update:modelValue'],
  setup(p, { emit }) {
    return () =>
      h(
        'button',
        {
          onClick: () => emit('update:modelValue', !p.modelValue),
          class: `w-10 h-5 rounded-full transition-colors relative ${p.modelValue ? 'bg-blue-500' : 'bg-gray-300'}`,
        },
        [
          h('span', {
            class: `absolute top-0.5 w-4 h-4 bg-white rounded-full shadow transition-all ${p.modelValue ? 'left-5' : 'left-0.5'}`,
          }),
        ],
      )
  },
})
</script>

<style scoped>
.si {
  display: block;
  width: 100%;
  padding: 7px 10px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 13px;
  color: #111827;
  background: #fff;
  outline: none;
  transition:
    border-color 0.15s,
    box-shadow 0.15s;
}
.si:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
textarea.si {
  resize: none;
}
</style>
