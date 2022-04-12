<template>
  <v-app id="inspire" :dark="isDark">
    <v-navigation-drawer
            clipped
            temporary
            fixed
            v-model="drawer"
            app
            v-if="loggedInUser"
    >
      <v-list dense>
        <router-link tag="v-list-tile" to="/">
          <v-list-tile-action>
            <v-icon>dashboard</v-icon>
          </v-list-tile-action>
          <v-list-tile-content>
            <v-list-tile-title>Dashboard</v-list-tile-title>
          </v-list-tile-content>
        </router-link>
        <router-link tag="v-list-tile" to="/settings">
          <v-list-tile-action>
            <v-icon>settings</v-icon>
          </v-list-tile-action>
          <v-list-tile-content>
            <v-list-tile-title>Settings</v-list-tile-title>
          </v-list-tile-content>
        </router-link>
        <v-divider></v-divider>
        <draggable
            v-if="favoriteForms.length"
            tag="v-list"
            style="transition: none;"
            :force-fallback="true"
            v-model="favoriteForms"
            v-bind="{group: 'favorites', handle: '.drag-handle', ghostClass: 'u-drag-ghost'}"
            @end="saveUserFavoriteForms">
          <v-list-tile
              :to="`/forms/${favorite.key}-form`"
              v-for="favorite in favoriteForms"
              :key="`${favorite.module}-${favorite.key}`"
              class="c-app__favorite-link">
            <v-list-tile-action class="drag-handle">
              <v-icon>drag_handle</v-icon>
            </v-list-tile-action>
            <v-list-tile-content>
              <v-list-tile-title>{{favorite.label}}</v-list-tile-title>
            </v-list-tile-content>
            <v-list-tile-action>
              <v-btn icon @click.stop.prevent="() => toggleFavoriteLink(favorite)">
                <v-icon color="yellow">star</v-icon>
              </v-btn>
            </v-list-tile-action>
          </v-list-tile>
        </draggable>
        <v-divider></v-divider>
        <v-subheader class="subheading">Forms</v-subheader>
        <v-list-group
            v-for="module in Object.keys($store.state.appForms)"
            :key="module"
            prepend-icon="check"
            no-action>
          <template v-slot:activator>
            <v-list-tile>
              <v-list-tile-content>
                <v-list-tile-title>{{module}}</v-list-tile-title>
              </v-list-tile-content>
            </v-list-tile>
          </template>
          <router-link tag="v-list-tile" :to="`/forms/${appForm.key}-form`" v-for="appForm in $store.state.appForms[module]" :key="appForm.key" class="c-app__form-link">
            <v-list-tile-content>
              <v-list-tile-title>{{appForm.label}}</v-list-tile-title>
            </v-list-tile-content>
            <v-list-tile-action>
              <v-btn
                  icon
                  @click.stop="() => toggleFavoriteLink(appForm)"
                  :class="{'c-app__favorite-btn': true, 'c-app__favorite-btn--active': favoriteForms.findIndex(item => item.module === module && item.key === appForm.key) > -1}">
                <v-icon :color="favoriteForms.findIndex(item => item.module === module && item.key === appForm.key) === -1 ? `grey` : `yellow`">star</v-icon>
              </v-btn>
            </v-list-tile-action>
          </router-link>
        </v-list-group>
        <v-divider></v-divider>
        <v-subheader class="subheading">Files</v-subheader>
        <router-link tag="v-list-tile" :to="`/forms/files-form`">
          <v-list-tile-action>
            <v-icon>folder</v-icon>
          </v-list-tile-action>
          <v-list-tile-content>
            <v-list-tile-title>Files Form</v-list-tile-title>
          </v-list-tile-content>
        </router-link>
        <router-link tag="v-list-tile" :to="`/files-upload`" v-if="!!$store.state.loggedInUser && !!$store.state.loggedInUser.roles.find(r => r === 'operator' || r === 'developer' || r === 'sa')">
          <v-list-tile-action>
            <v-icon>cloud_upload</v-icon>
          </v-list-tile-action>
          <v-list-tile-content>
            <v-list-tile-title>Files Upload</v-list-tile-title>
          </v-list-tile-content>
        </router-link>
        <v-divider></v-divider>
        <v-list-tile :href="helpLink" target="_blank">
          <v-list-tile-action>
            <v-icon>help_outline</v-icon>
          </v-list-tile-action>
          <v-list-tile-content>
            <v-list-tile-title>Help</v-list-tile-title>
          </v-list-tile-content>
        </v-list-tile>
      </v-list>
    </v-navigation-drawer>
    <v-toolbar app fixed dense clipped-left v-if="loggedInUser">
      <v-toolbar-side-icon @click.stop="drawer = !drawer"></v-toolbar-side-icon>
      <v-toolbar-title>
        <router-link to="/">
          <v-img :src="appIcon" style="display: inline-block; vertical-align: middle; width: 60px;"></v-img>
        </router-link>
        {{ appShortName }}
      </v-toolbar-title>
      <v-spacer></v-spacer>
      <v-menu open-on-hover offset-y>
        <template  v-slot:activator="data">
          <v-btn icon v-on="data.on">
            <v-icon>person</v-icon>
          </v-btn>
        </template>
        <v-list>
          <v-list-tile to="/settings/account">
            <v-list-tile-title>{{loggedInUser.profile.name}} (@{{loggedInUser.username}})</v-list-tile-title>
          </v-list-tile>
          <v-list-tile @click="logout">
            <v-list-tile-title>Logout</v-list-tile-title>
          </v-list-tile>
        </v-list>
      </v-menu>
    </v-toolbar>
    <v-content :class="{'v-content-compact': $store.state.compactUI}">
      <v-layout>
        <v-flex>
          <dis-breadcrumbs />
        </v-flex>
      </v-layout>
      <v-fade-transition>
        <router-view v-if="!isLoading"></router-view>
      </v-fade-transition>
    </v-content>
    <v-footer app fixed>
      <div><a class="footer-link" :href="copyrightLink">&copy; {{ copyrightText }}</a></div>
      <v-spacer></v-spacer>
      <div><a class="footer-link" :href="aboutLink">About</a></div>
      <v-spacer></v-spacer>
      <v-dialog v-model="impressumDialog" full-width>
        <template v-slot:activator="{ on }">
          <v-btn flat v-on="on" class="footer-link" color="primary">Impressum</v-btn>
        </template>
        <v-card>
          <v-card-text>
            <impressum></impressum>
          </v-card-text>
        </v-card>
      </v-dialog>
      <div id="footer_buttons">
        <v-tooltip top>
          <template v-slot:activator="{ on, attrs }">
            <v-btn icon small v-bind="attrs" v-on="on" @click="toggleCompactUI">
              <v-icon small>swap_vert</v-icon>
            </v-btn>
          </template>
          <span>Toggle Compact UI</span>
        </v-tooltip>
        <v-tooltip top>
          <template v-slot:activator="{ on, attrs }">
            <v-btn icon small v-bind="attrs" v-on="on" @click="switchTheme">
              <v-icon small>invert_colors</v-icon>
            </v-btn>
          </template>
          <span>Toggle Theme</span>
        </v-tooltip>
      </div>
    </v-footer>
  </v-app>
</template>

<script>
import BackendService from './services/BackendService'
import { mapGetters, mapMutations } from 'vuex'
import ls from 'local-storage'
import Impressum from './components/Impressum'
import debounce from './util/debounce'
import draggable from 'vuedraggable'
// import AppService from './services/AppService'

window.baseUrl = location.pathname.replace(/\/[^/]*$/, '/')

export default {
  name: 'App',
  components: { Impressum, draggable },
  data: () => ({
    drawer: false,
    impressumDialog: false,
    favoriteForms: [],
    isLoading: true
  }),
  created () {
    this.backendService = new BackendService()
    this.saveUserFavoriteForms = debounce(this.saveUserFavoriteForms, 1000)
    try {
      this.$store.dispatch('initApp')
        .then(() => {
          this.isLoading = false
          this.favoriteForms = this.$store.state.config.userConfig['favorite-forms'] || []
        })
        .catch((errorMessage) => {
          this.isLoading = false
          // Call of initApp failed. If that was because of an expired user token (Code 401),
          // redirect to /login already happened in bootstrap.js
          let error = BackendService.lastError
          if (error.status !== 401) {
            // Only log error, if other than 401
            console.log('App.created() initApp failed:', errorMessage, error)
          }
        })
      this.setIsDark(ls.get('theme') !== 'light')
    } catch (error) {
      // this.$dialog.notify.warning(error.message, {timeout: 30000})
      console.log(error)
    }
  },
  methods: {
    ...mapMutations({
      setIsDark: 'IS_DARK',
      toggleCompact: 'TOGGLE_COMPACT_UI'
    }),
    switchTheme () {
      this.setIsDark(!this.isDark)
      ls.set('theme', this.isDark ? 'dark' : 'light')
    },
    toggleCompactUI () {
      this.toggleCompact()
      ls.set('compact', !!this.compactUI)
    },
    async logout () {
      try {
        await this.backendService.logout()
      } catch (e) {
        console.warn(e)
      } finally {
        this.$store.commit('SET_LOGGED_IN_USER', null)
        this.$router.push('/login')
      }
    },
    toggleFavoriteLink (form) {
      const findIndex = this.favoriteForms.findIndex(item => item.module === form.module && item.key === form.key)
      if (findIndex > -1) {
        this.favoriteForms = this.favoriteForms.filter((item, index) => index !== findIndex)
      } else {
        this.favoriteForms.push({ module: form.module, key: form.key, label: form.label })
      }
      this.saveUserFavoriteForms()
    },
    async saveUserFavoriteForms () {
      console.log('saveUserFavoritesForms')
      await this.$store.dispatch('config/saveUserConfig', {
        'favorite-forms': this.favoriteForms
      })
      // this.favoriteForms = this.$store.state.config.userConfig['favorite-forms']
    }
  },
  computed: {
    ...mapGetters({
      loggedInUser: 'loggedInUser',
      isDark: 'isDark',
      compactUI: 'getCompactUI',
      appShortName: 'config/appShortName',
      appName: 'config/appName',
      appIcon: 'config/appIcon',
      copyrightText: 'config/copyrightText',
      copyrightLink: 'config/copyrightLink',
      aboutLink: 'config/aboutLink',
      helpLink: 'config/helpLink'
    }),
    menuFavorites: {
      get () {
        return this.favoriteForms
      },
      set (favorites) {
        this.favoriteForms = favorites
        this.saveUserFavoriteForms()
      }
    },
    stateFavoriteForms () {
      return this.$store.state.config.userConfig['favorite-forms']
    }
  },
  watch: {
    stateFavoriteForms (newFavoriteForms) {
      this.favoriteForms = newFavoriteForms || []
    }
  }
}
</script>

<style scoped>
.c-app__form-link .c-app__favorite-btn {
  display: none;
}
.c-app__form-link:hover .c-app__favorite-btn {
  display: block;
}
.c-app__form-link .c-app__favorite-btn--active {
  display: block;
}
.footer-link {
  text-decoration: none;
  text-transform: uppercase;
  font-weight: normal;
}
.v-navigation-drawer--temporary {
  z-index: 1010;
}

#footer_buttons::before{
  content: "";
  border-left: 2px solid whitesmoke ;
  padding-right: 3px;
}
</style>
