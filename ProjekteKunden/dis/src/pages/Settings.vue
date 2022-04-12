<template>
    <v-container fluid grid-list-md>
        <v-layout row wrap>
            <v-flex xs12 sm6 md4 lg3 xl2 v-if="userIsAdmin">
                <v-card color="primary">
                    <v-card-title>
                        User Management
                    </v-card-title>
                    <v-card-text>
                        Add or block users, manage role assignments and more.
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn
                                :href="baseUrl + 'user/admin/index'"
                                target="_blank"
                                flat>
                            Open
                            <v-icon right>open_in_new</v-icon>
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-flex>
            <v-flex xs12 sm6 md4 lg3 xl2 v-if="userIsDeveloper">
                <v-card color="lime darken-4" dark>
                    <v-card-title>
                        Template Manager
                    </v-card-title>
                    <v-card-text>
                        Manage Model &amp; Forms templates and generate corresponding code
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn to="/settings/templates-manager" flat>
                            Go To Template Manager
                            <v-icon right>show_chart</v-icon>
                        </v-btn>
                    </v-card-actions>
                </v-card>
            </v-flex>
            <v-flex xs12 sm6 md4 lg3 xl2 v-if="userIsDeveloper">
              <v-card color="teal darken-1" dark>
                <v-card-title>
                  My Account
                </v-card-title>
                <v-card-text>
                  Change your user account settings
                </v-card-text>
                <v-card-actions>
                  <v-spacer></v-spacer>
                  <v-btn to="/settings/account" flat>
                    Account Settings
                    <v-icon right>person</v-icon>
                  </v-btn>
                </v-card-actions>
              </v-card>
            </v-flex>
        </v-layout>
    </v-container>
</template>

<script>
export default {
  name: 'Settings',
  data () {
    return {
      baseUrl: window.baseUrl
    }
  },
  mounted () {
    this.$setTitle('Settings')
  }
}
</script>

<style scoped>

</style>
