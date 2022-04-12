<template>
  <v-container fluid grid-list-md fill-height class="pt-0">
    <v-progress-linear class="fixed-top" v-if="isChecking" indeterminate/>
    <v-layout justify-center align-center v-if="!isChecking">
      <div v-if="!canReset">
        <v-flex shrink>
            <span class="not-valid">
                Not Valid
            </span>
        </v-flex>
        <v-flex shrink>
          <p class="title">
            {{ notValidText }}
          </p>
          <router-link to="/">Back</router-link>
        </v-flex>
      </div>
      <v-flex shrink v-else>
        <v-card class="pa-4" v-if="!passwordChangedText && canReset">
          <v-form @keyup.native.enter="submit" ref="form" lazy-validation >
            <v-card-text>
              <h1>Reset password</h1>
              <v-text-field
                  v-model="password"
                  :rules="[v => !!v || 'This is required']"
                  label="New password"
                  :type="passwordFieldType"
                  autocorrect="off"
                  autocapitalize="none"
                  :error-messages="errorMessages"
                  :append-icon="passwordFieldType === 'text' ? 'visibility_off' : 'visibility'"
                  @click:append="switchVisibility"
              >
              </v-text-field>
            </v-card-text>
            <v-card-actions>
              <v-btn :loading="loading" :disabled="loading" color="primary" outline @click="submit" block>submit</v-btn>
            </v-card-actions>
          </v-form>
        </v-card>
        <v-card class="pa-4" v-else>
          <v-card-text>
            <v-icon x-large color="green" class="full-width">check</v-icon>
            <v-spacer></v-spacer>
            <p>{{ passwordChangedText }}</p>
          </v-card-text>
          <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn to="/login" flat>
              Go to Login
              <v-icon right>open_in_browser</v-icon>
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-flex>
    </v-layout>
  </v-container>
</template>

<script>
import AccountService from '@/services/AccountService'
import ls from 'local-storage'

export default {
  name: 'ResetPassword',
  data () {
    return {
      password: '',
      passwordFieldType: 'password',
      errorMessages: [],
      loading: false,
      isChecking: true,
      notValidText: null,
      passwordChangedText: null,
      canReset: false
    }
  },
  async mounted () {
    this.$setTitle('Reset password')
    this.accountService = new AccountService()
    try {
      await this.accountService.checkRecoveryLink(this.$route.params.userId, this.$route.params.code)
      this.isChecking = false
      this.canReset = true
    } catch (e) {
      this.isChecking = false
      if (e.response && e.response.status === 422) {
        this.notValidText = e.response.data.message
      }
    }
  },
  methods: {
    clear () {
      this.$refs.form.reset()
      this.errorMessages = []
    },
    switchVisibility () {
      this.passwordFieldType =
        this.passwordFieldType === 'password' ? 'text' : 'password'
    },
    async submit () {
      if (this.$refs.form.validate()) {
        this.loading = true
        try {
          await this.accountService.resetPassord(this.$route.params.userId, this.$route.params.code, this.password)
          this.passwordChangedText = 'Congratulations, Your password has been changed.'
          ls.remove('recovery-email-sent_at')
          this.loading = false
        } catch (error) {
          this.loading = false
          if (error.response && error.response.status === 422) {
            this.errorMessages = error.response.data.map(
              item => item.message
            )
          }
        }
      }
    }
  }
}
</script>

<style scoped>
.fixed-top {
  position: fixed;
  top: 0;
}

.full-width {
  width: 100%;
}

.not-valid {
  font-size: 10rem;
  margin-right: 10px;
}
</style>
