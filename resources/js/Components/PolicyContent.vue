<template>
  <div class="policy-content-body">
    <h1>{{ t('common.privacy_and_cookie_policy') }}</h1>
    <p class="policy-date">{{ t('components.policycontent.last_updated_2025') }}</p>

    <p>
      {{ t('components.policycontent.thank_you_for_reading_our_privacy') }} <strong>RODO</strong>
      {{ t('components.policycontent.regulation_eu_2016_679_of_the') }}
    </p>

    <h2 id="administrator">1. Administrator Danych Osobowych</h2>
    <p>{{ t('components.policycontent.the_controller_of_your_personal_data') }}</p>
    <div class="admin-details">
      <p>
        <strong>{{ shopName || t('common.shop') }}</strong>
      </p>
      <p>Email: {{ shopEmail }}</p>
    </div>

    <h2 id="dane-zbierane">2. Rodzaje zbieranych danych</h2>
    <p>{{ t('components.policycontent.we_collect_data_in_two_main') }}</p>
    <ul>
      <li>
        <strong>{{ t('components.policycontent.data_you_give_us') }}</strong>
        {{ t('components.policycontent.first_name_surname_email_address_and') }}
      </li>
      <li>
        <strong>{{ t('components.policycontent.data_collected_automatically_cookies_and_analytics') }}</strong>
        {{ t('components.policycontent.ip_address_browser_details_time_spent') }}
      </li>
    </ul>

    <h2 id="cel-przetwarzania">3. Cel i Podstawa Prawna Przetwarzania</h2>
    <table>
      <thead>
        <tr>
          <th>{{ t('components.policycontent.purpose_of_processing') }}</th>
          <th>{{ t('components.policycontent.legal_basis_gdpr') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>{{ t('components.policycontent.fulfilling_orders_and_reservations') }}</td>
          <td>Art. 6 ust. 1 lit. b RODO (wykonanie umowy)</td>
        </tr>
        <tr>
          <td>{{ t('components.policycontent.keeping_statistics_and_analysing_traffic') }}</td>
          <td>Art. 6 ust. 1 lit. a RODO (Twoja dobrowolna zgoda na pliki cookie)</td>
        </tr>
        <tr>
          <td>{{ t('components.policycontent.showing_personalised_ads') }}</td>
          <td>Art. 6 ust. 1 lit. a RODO (Twoja dobrowolna zgoda na pliki cookie)</td>
        </tr>
      </tbody>
    </table>

    <h2 id="ciasteczka">{{ t('components.policycontent.4_cookie_policy') }}</h2>
    <p>
      {{ t('components.policycontent.this_site_uses_cookies_so_that') }}
      <strong>{{ t('components.policycontent.provided_you_have_consented') }}</strong
      >.
    </p>

    <h3>{{ t('components.policycontent.the_kinds_of_cookie_we_use') }}</h3>
    <ul>
      <li>
        <strong>{{ t('components.policycontent.essential_required') }}</strong>
        {{ t('components.policycontent.they_make_the_basics_work_signing') }}
      </li>
      <li>
        <strong>{{ t('components.policycontent.analytics') }}</strong>
        {{ t('components.policycontent.they_follow_how_the_site_is') }}
      </li>
      <li>
        <strong>{{ t('components.policycontent.marketing') }}</strong>
        {{ t('components.policycontent.they_are_used_to_target_advertising') }}
      </li>
    </ul>

    <h2 id="prawa-uzytkownika">{{ t('components.policycontent.5_your_rights') }}</h2>
    <p>{{ t('components.policycontent.you_have_the_right_to') }}</p>
    <ul>
      <li>{{ t('components.policycontent.request_access_to_your_personal_data') }}</li>
      <li>{{ t('components.policycontent.have_your_data_corrected_deleted_or') }}</li>
      <li>{{ t('components.policycontent.object_to_your_data_being_processed') }}</li>
      <li>{{ t('components.policycontent.have_your_data_moved_elsewhere') }}</li>
      <li>{{ t('components.policycontent.withdraw_your_consent_at_any_time') }}</li>
      <li>{{ t('components.policycontent.lodge_a_complaint_with_the_supervisory') }}</li>
    </ul>

    <h2 id="kontakt">6. Kontakt</h2>
    <p>
      {{ t('components.policycontent.for_anything_to_do_with_personal') }} <strong>{{ shopEmail }}</strong
      >.
    </p>
  </div>
</template>

<script setup>
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps({
  shopName: { type: String, default: '' },
  shopEmail: { type: String, default: '' },
})
</script>

<style scoped>
.policy-content-body {
  font-family: sans-serif;
  color: #212121;
  max-width: 100%;
  line-height: 1.7;
}
.policy-content-body h1 {
  font-size: 24px;
  font-weight: 700;
  margin-bottom: 10px;
}
.policy-content-body h2 {
  font-size: 18px;
  font-weight: 600;
  margin-top: 25px;
  margin-bottom: 10px;
  border-bottom: 1px solid #ddd;
  padding-bottom: 5px;
}
.policy-content-body h3 {
  font-size: 16px;
  font-weight: 600;
  margin-top: 15px;
  margin-bottom: 5px;
}
.policy-date {
  font-style: italic;
  font-size: 13px;
  color: #888;
  margin-bottom: 20px;
}
.policy-content-body p,
.policy-content-body li {
  font-size: 14px;
  margin-bottom: 10px;
}
.admin-details {
  background: #f9f9f9;
  border: 1px solid #eee;
  padding: 15px;
  border-radius: 4px;
  margin-bottom: 20px;
  font-size: 14px;
}
.admin-details p {
  margin: 0;
}
.policy-content-body ul {
  padding-left: 20px;
  list-style: disc;
}
table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
}
th,
td {
  border: 1px solid #eee;
  padding: 10px;
  text-align: left;
  font-size: 13px;
}
th {
  background-color: #f4f4f4;
  font-weight: 600;
}
</style>
