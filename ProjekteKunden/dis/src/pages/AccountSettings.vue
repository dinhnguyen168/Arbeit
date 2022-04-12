<template>
  <v-container fluid grid-list-md>
    <v-layout wrap>
      <v-progress-linear v-if="!profileForm.email === ''" indeterminate />
      <v-flex sm12 md6 lg4>
        <v-card color="blue-grey" dark>
          <v-card-title>
            My Profile
          </v-card-title>
          <v-card-text>
            <v-form>
              <v-text-field v-model="profileForm.name" label="Name"/>
              <v-text-field v-model="profileForm.email" label="Email"/>
              <dis-server-validation-errors-view v-model="profileFormValidationErrors"></dis-server-validation-errors-view>
              <v-btn :loading="isSavingProfile" color="success" @click="onSaveProfileClick">Save</v-btn>
            </v-form>
          </v-card-text>
        </v-card>
      </v-flex>
      <v-flex sm12 md6 lg4>
        <v-card color="blue-grey" dark>
          <v-card-title>
            Change Password
          </v-card-title>
          <v-card-text>
            <v-form>
              <v-text-field v-model="changePasswordForm.oldPassword" type="password" label="Old Password"/>
              <v-text-field v-model="changePasswordForm.newPassword" type="password" label="New Password"/>
              <v-text-field v-model="changePasswordForm.newPasswordConfirmation" type="password" label="New Password Confirmation"/>
              <dis-server-validation-errors-view v-model="changePasswordFormValidationErrors"></dis-server-validation-errors-view>
              <v-btn :loading="isChangingPassword" color="success" @click="onChangePasswordClick">Change</v-btn>
            </v-form>
          </v-card-text>
        </v-card>
      </v-flex>
      <v-flex sm12 md6 lg4>
        <v-card color="blue-grey" dark>
          <v-card-title>
            Assigned Permissions
          </v-card-title>
          <v-card-text>
            <v-chip v-for="(permission, i) in $store.state.loggedInUser.permissions" :key="i">{{permission}}</v-chip>
          </v-card-text>
        </v-card>
      </v-flex>
    </v-layout>
  </v-container>
</template>

<script>

export default {
  name: 'AccountSettings',
  data () {
    return {
      isLoading: false,
      profileForm: {
        name: '',
        email: ''
      },
      profileFormValidationErrors: [],
      isSavingProfile: false,
      changePasswordForm: {
        oldPassword: '',
        newPassword: '',
        newPasswordConfirmation: ''
      },
      changePasswordFormValidationErrors: [],
      isChangingPassword: false
    }
  },
  created () {
    this.refreshProfile()
  },
  methods: {
    async refreshProfile () {
      await this.$store.dispatch('refreshUser')
      this.profileForm.name = this.$store.state.loggedInUser.profile.name
      this.profileForm.email = this.$store.state.loggedInUser.email
    },
    async onSaveProfileClick () {
      try {
        this.isSavingProfile = true
        this.profileFormValidationErrors = []
        await this.$store.dispatch('account/updateProfile', this.profileForm)
        await this.refreshProfile()
      } catch (e) {
        if (e.response && e.response.status === 422) {
          this.profileFormValidationErrors = e.response.data
        }
      } finally {
        this.isSavingProfile = false
      }
    },
    async onChangePasswordClick () {
      try {
        this.isChangingPassword = true
        this.changePasswordFormValidationErrors = []
        await this.$store.dispatch('account/changePassword', this.changePasswordForm)
        this.$dialog.message.success('Password updated successfully')
        this.changePasswordForm = {
          oldPassword: '',
          newPassword: '',
          newPasswordConfirmation: ''
        }
        await this.refreshProfile()
      } catch (e) {
        if (e.response && e.response.status === 422) {
          this.changePasswordFormValidationErrors = e.response.data
        }
      } finally {
        this.isChangingPassword = false
      }
    }
  }
}
</script>
