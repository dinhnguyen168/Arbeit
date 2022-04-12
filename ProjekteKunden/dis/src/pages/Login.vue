<template>
  <v-layout justify-center fill-height align-center column class="c-dis-login">
    <div class="c-dis-login__title">
      <h1>{{ appName }}</h1>
      <div class="c-dis-login__underline">
        <div></div>
        <div></div>
        <div></div>
        <div></div>
      </div>
    </div>
    <v-flex shrink>
      <v-card class="pa-4">
        <v-form @keyup.native.enter="submit" ref="loginForm" lazy-validation>
          <v-card-text>
            <h1>Login to {{ appShortName }}</h1>
            <v-text-field
              v-model="username"
              autocorrect="off"
              autocapitalize="none"
              :rules="[v => !!v || 'This is required']"
              label="Username / Email"
            ></v-text-field>
            <v-text-field
              v-model="password"
              :rules="[v => !!v || 'This is required']"
              label="Password"
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
            <v-btn :loading="loading" :disabled="loading" color="primary" outline @click="submit" block>Log In</v-btn>
          </v-card-actions>
          <v-card-text></v-card-text>
          <v-card-actions>
            <v-dialog
                v-model="dialog"
                persistent
                max-width="600px"
            >
              <template v-slot:activator="{ on, attrs }">
                <v-btn
                    v-if="canSendEmails"
                    color="primary"
                    flat
                    block
                    @click=" () => dialog = true"
                >
                  Forgot Password
                </v-btn>
              </template>
              <v-card class="pa-4">
                <v-form @keyup.native.enter="send" ref="resendForm" lazy-validation>
                  <v-card-text v-if="canSend && !sent">
                    <h1>Reset password</h1>
                    <p>Enter your email to send a reset link</p>
                    <v-text-field
                        v-model="username"
                        autocorrect="off"
                        autocapitalize="none"
                        :rules="[v => !!v || 'This is required', v => emailReg.test(v) || 'Please enter a valid email'] "
                        label="Email"
                    ></v-text-field>
                  </v-card-text>
                  <v-card-text v-else>
                    <v-icon x-large color="green" class="full-width">check</v-icon>
                    <v-spacer></v-spacer>
                    <p>An email has been sent with instructions for resetting your password. You will be able to send a new request after 6 hours since your last request.</p>
                  </v-card-text>
                  <v-card-actions>
                    <v-btn color="primary" flat block @click="() => dialog = false">Close</v-btn>
                    <v-btn :loading="sending" :disabled="!canSend && sent" color="primary" outline @click="send" block>Send</v-btn>
                  </v-card-actions>
                </v-form>
              </v-card>
            </v-dialog>
            <!-- <v-btn color="primary" flat block @click="onForgotPasswordClick">Forgot Password</v-btn> -->
          </v-card-actions>
        </v-form>
      </v-card>
    </v-flex>
  </v-layout>
</template>

<script>
import BackendService from '../services/BackendService'
import AccountService from '@/services/AccountService'
import { mapGetters } from 'vuex'
import ls from 'local-storage'

export default {
  name: 'Login',
  data () {
    return {
      username: '',
      password: '',
      passwordFieldType: 'password',
      errorMessages: [],
      loading: false,
      sending: false,
      dialog: false,
      sent: false,
      // eslint-disable-next-line no-useless-escape
      emailReg: /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+$/,
      canSend: true
    }
  },
  mounted () {
    this.$setTitle('Login')
    this.backendService = new BackendService()
    this.accountService = new AccountService()
    const sentAt = ls.get('recovery-email-sent_at')
    if (sentAt !== null) {
      if ((sentAt + 21600000) > Date.now()) {
        this.canSend = false
        this.sent = true
      }
    }
  },
  computed: {
    ...mapGetters({
      appShortName: 'config/appShortName',
      appName: 'config/appName',
      canSendEmails: 'config/canSendEmails'
    })
  },
  methods: {
    clear () {
      this.$refs.form.reset()
      this.errorMessages = []
      this.dialog = false
      this.sent = false
    },
    switchVisibility () {
      this.passwordFieldType =
        this.passwordFieldType === 'password' ? 'text' : 'password'
    },
    submit () {
      if (this.$refs.loginForm.validate()) {
        this.loading = true
        this.backendService
          .login({ username: this.username, password: this.password })
          .then(async userData => {
            this.$store.commit('SET_LOGGED_IN_USER', userData)
            // this.$store.dispatch('templates/refreshSummary')
            this.$store.dispatch('initApp')
            if (
              this.$route.query.redirect &&
              this.$route.query.redirect !== '/login'
            ) {
              this.$router.push(this.$route.query.redirect)
            } else {
              this.$router.push('/')
            }
            this.loading = false
          })
          .catch(error => {
            this.loading = false
            if (error.response && error.response.status === 422) {
              this.errorMessages = error.response.data.map(
                item => item.message
              )
            }
          })
      }
    },
    async send () {
      if (this.$refs.resendForm.validate()) {
        this.sending = true
        try {
          await this.accountService.requestRecoveryEmail(this.username)
          ls.set('recovery-email-sent_at', Date.now())
          this.sent = true
          this.sending = false
          this.canSend = false
        } catch (e) {
          this.sending = false
          if (e.response && e.response.status === 422) {
            if (e.response.data.kind === 'still_valid') {
              this.$dialog.message.warning(e.response.data.message)
            } else if (e.response.data.kind === 'not_found') {
              this.$dialog.message.warning(e.response.data.message)
            } else if (e.response.data.kind === 'is_ldap') {
              this.$dialog.message.warning(e.response.data.message)
            } else {
              this.$dialog.message.warning('Invalid email')
            }
          }
        }
      }
    }
  }
}
</script>

<style scoped>
.full-width {
  width: 100%;
}
</style>
