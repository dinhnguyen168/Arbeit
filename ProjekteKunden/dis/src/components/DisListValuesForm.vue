<template>
    <v-container fluid>
        <form lazy-validation>
                <dis-auto-increment-input
                        v-model.number="formModel['id']"
                        :serverValidationErrors="serverValidationErrors"
                        name="id"
                        label="ID"
                        :validators="[]"
                ></dis-auto-increment-input>
              <input type="hidden" v-show="false" v-model="formModel.listname"/>
              <v-tabs slider-color="primary">
                <v-tab key="items" ripple>
                  Items
                </v-tab>
                <v-tab key="list" ripple>
                  List Options
                </v-tab>
                <v-tab-item key="items">
                  <v-card flat>
                    <v-card-text>
                      <v-layout wrap>
                        <v-flex md3 lg3 sm6 xs12 pr-2 pl-2>
                          <DisTextInput
                              :validators="[{ type: 'number' }]"
                              name="sort"
                              label="Sort Index"
                              :serverValidationErrors="serverValidationErrors"
                              :disabled="!canEditList"
                              v-model.number="formModel.sort"/>
                        </v-flex>
                        <v-flex md3 lg3 sm6 xs12 pr-2 pl-2>
                          <DisTextInput
                              :validators="[]"
                              name="display"
                              label="Abbreviation"
                              :serverValidationErrors="serverValidationErrors"
                              :disabled="!canEditList"
                              v-model.number="formModel.display"/>
                        </v-flex>
                        <v-flex md3 lg3 sm6 xs12 pr-2 pl-2>
                          <DisTextInput
                              :validators="[]"
                              name="remark"
                              label="Description"
                              :serverValidationErrors="serverValidationErrors"
                              :disabled="!canEditList"
                              v-model.number="formModel.remark"/>
                        </v-flex>
                      </v-layout>
                      <v-btn @click="save" :loading="loading" :color="formModel.id ? 'green darken-2' : 'blue'" :disabled="!canEditList">
                        {{formModel.id ? 'save' : 'create'}}
                      </v-btn>
                      <v-btn @click="resetForm" :disabled="loading || !canEditList" color="red lighten-2">
                        cancel
                      </v-btn>
                    </v-card-text>
                  </v-card>
                </v-tab-item>
                <v-tab-item key="list">
                  <v-card flat>
                    <v-card-text>
                      <v-layout>
                        <v-flex md3 lg3 sm6 xs12 pr-2 pl-2>
                          List is {{listInfo.is_locked ? 'Locked' : 'Unlocked'}}
                          <v-btn @click="toggleListLock" :loading="loading" :disabled="!userIsAdmin">{{listInfo.is_locked ? 'Unlock' : 'Lock'}}</v-btn>
                        </v-flex>
                        <v-flex md3 lg3 sm6 xs12 pr-2 pl-2>
                          <v-text-field v-model="listInfo.list_uri" append-icon="save" @click:append="updateListUri" :disabled="!canEditList">
  <!--                          <v-slot ></v-slot>-->
                          </v-text-field>
                        </v-flex>
                        <v-flex md3 lg3 sm6 xs12 pr-2 pl-2 v-if="userIsAdmin">
                          <v-btn color="green" @click="exportValueList" :disabled="items.length === 0">Export value list</v-btn>
                        </v-flex>
                      </v-layout>
                    </v-card-text>
                  </v-card>
                </v-tab-item>
              </v-tabs>
        </form>
        <v-alert :value="formModel.listname === 'UPLOAD_FILE_TYPE'" color="info" icon="warning">
            Please be aware that the value in the &apos;Abbreviation&apos; field in this list will be used as a subdirectory name when assigning files. Use only valid characters (a-z, A-Z, _, -) without spaces. Every name should be unique.
        </v-alert>
        <v-data-table
                ref="table"
                :headers="tableHeaders"
                :items="items"
                :pagination.sync="pagination"
                :total-items="totalItems"
                :loading="loading"
                :hide-actions="true"
        >
            <template v-slot:items="props">
                <tr :active="props.selected" @click="props.selected = !props.selected">
                    <td class="text-xs-left">{{ props.item.display }}</td>
                    <td class="text-xs-left">{{ props.item.remark }}</td>
                    <td class="text-xs-left">{{ props.item.sort }}</td>
                    <td class="text-xs-right">
                        <v-btn color="orange" title="edit" icon @click="() => editItem(props.item)" :disabled="!canEditList">
                            <v-icon>
                                edit
                            </v-icon>
                        </v-btn>
                        <v-btn color="red" title="delete" icon @click="() => deleteItem(props.item)" :disabled="!canEditList">
                            <v-icon>
                                delete
                            </v-icon>
                        </v-btn>
                    </td>
                </tr>
            </template>
        </v-data-table>
    </v-container>
</template>

<script>
import ListValuesService from '../services/ListValuesService'

export default {
  name: 'DisListValuesForm',
  props: [
    'listName'
  ],
  data () {
    return {
      serverValidationErrors: [],
      formScenario: 'view',
      selectedItem: null,
      loading: false,
      items: [],
      totalItems: 0,
      pagination: {
        descending: false,
        page: 1,
        rowsPerPage: -1,
        sortBy: 'sort',
        totalItems: 0
      },
      formModel: {
        id: null,
        listname: '',
        sort: 0,
        display: '',
        remark: ''
      },
      refreshOnPagination: true,
      listInfo: {
        list_uri: null,
        isLocked: false
      },
      baseUrl: window.baseUrl
    }
  },
  computed: {
    tableHeaders () {
      return [
        {
          text: 'Display',
          value: 'display'
        },
        {
          text: 'Remark',
          value: 'remark'
        },
        {
          text: 'Sort Rank',
          value: 'sort'
        },
        {
          text: '',
          value: '',
          sortable: false
        }
      ]
    },
    canEditList () {
      return !this.listInfo.is_locked || this.userIsAdmin
    }
  },
  created () {
    if (this.listName) {
      this.formModel.listname = this.listName
    }
    this.service = new ListValuesService()
    this.refreshListInfo()
  },
  methods: {
    async refreshItems () {
      this.refreshOnPagination = false
      this.loading = true
      try {
        if (!this.formModel.listname) {
          this.items = []
          this.totalItems = []
          this.pagination.page = 1
        } else {
          let queryParams = this.pagination.rowsPerPage > 0 ? {
            'per-page': this.pagination.rowsPerPage,
            'page': this.pagination.page,
            'sort': `${(this.pagination.descending) ? '-' : ''}${this.pagination.sortBy}`
          } : {
            'per-page': -1
          }
          let data = await this.service.getList(queryParams, { listname: this.formModel.listname })
          this.items = data.items
          // this.totalItems = data._meta.totalCount
          // this.pagination.page = data._meta.currentPage
          return true
        }
      } catch (e) {
        this.$dialog.notify.warning('unable to load list items')
        console.log(e)
      } finally {
        this.refreshOnPagination = true
        this.loading = false
      }
    },
    resetForm () {
      this.serverValidationErrors = []
      this.formModel = Object.assign(this.formModel, { id: null, sort: 0, display: '', remark: '' })
    },
    editItem (item) {
      this.formModel = Object.assign(this.formModel, item)
    },
    async save () {
      this.serverValidationErrors = []
      if (!this.formModel.id) {
        // create
        this.loading = true
        try {
          await this.service.post(this.formModel)
          this.$dialog.message.success('created successfully')
          this.resetForm()
          await this.refreshItems()
        } catch (error) {
          if (error.response.status === 422) {
            this.serverValidationErrors = error.response.data
          } else {
            this.$dialog.notify.warning('unable to create new list item')
            console.log(error)
          }
        } finally {
          this.loading = false
        }
      } else {
        // update
        this.loading = true
        try {
          await this.service.put(this.formModel.id, this.formModel)
          this.$dialog.message.success('updated successfully')
          this.resetForm()
          await this.refreshItems()
        } catch (error) {
          if (error.response.status === 422) {
            this.serverValidationErrors = error.response.data
          } else {
            this.$dialog.notify.warning('unable to edit list item')
            console.log(error)
          }
        } finally {
          this.loading = false
        }
      }
    },
    async deleteItem (item) {
      console.log('DELETE', item)
      let confirm = await this.$dialog.confirm({
        title: 'Delete Item',
        text: `Are you sure you want to delete item <strong>${item.display}</strong>`
      })
      if (confirm) {
        this.loading = true
        try {
          await this.service.delete(item.id)
          this.$dialog.message.success('deleted successfully')
          await this.refreshItems()
        } catch (e) {
          this.$dialog.notify.warning('an error happend while deleting the item')
          console.log(e)
        } finally {
          this.loading = false
        }
      }
    },
    async refreshListInfo () {
      this.loading = true
      try {
        const response = await this.service.getListInfo(this.formModel.listname)
        console.log(response.data)
        this.listInfo = response.data
        return true
      } catch (e) {
        this.$dialog.notify.warning('unable to load list info')
        console.log(e)
      } finally {
        this.loading = false
      }
    },
    async toggleListLock () {
      this.loading = true
      try {
        await this.service.updateListInfo(this.formModel.listname, { is_locked: !this.listInfo.is_locked })
        await this.refreshListInfo()
      } catch (e) {
        this.$dialog.notify.warning('unable to toggle list lock')
        console.log(e)
      } finally {
        this.loading = false
      }
    },
    async updateListUri () {
      this.loading = true
      try {
        await this.service.updateListInfo(this.formModel.listname, { list_uri: this.listInfo.list_uri })
        await this.refreshListInfo()
      } catch (e) {
        this.$dialog.notify.warning('unable to update list uri')
        console.log(e)
      } finally {
        this.loading = false
      }
    },
    exportValueList () {
      let url = `${this.baseUrl}report/ExportValueList?listname=${this.formModel.listname}`
      console.log('exportValueList: url', url)
      window.open(url)
    }
  },
  watch: {
    pagination: {
      deep: true,
      handler () {
        if (this.refreshOnPagination) {
          this.refreshItems()
        }
      }
    }
  }
}
</script>

<style scoped>

</style>
