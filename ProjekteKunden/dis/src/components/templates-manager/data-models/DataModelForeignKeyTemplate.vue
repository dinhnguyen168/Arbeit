<template>
    <div :class="Object.assign({'c-index-template': true}, themeClasses)">
        <dis-simple-panel>
            <template v-slot:head>
                <h3 >{{value.name}}</h3>
            </template>
            <v-flex pa-2 sm12 md6 lg4>
                <v-card>
                    <v-card-text>
                        <v-btn @click="$emit('remove')" color="error" :disabled="value.isLocked || isParentRelation">Remove Relation</v-btn>
                    </v-card-text>
                </v-card>
            </v-flex>
            <v-flex pa-2 sm12 md6 lg4>
              <v-card>
                <v-card-text>
                  <v-select
                      v-if="!isParentRelation"
                      chips
                      label="Relation type"
                      v-model="value.relationType"
                      :items="relationTypes"
                      :disabled="!!value.relationType"
                  />
                  <v-select
                      v-if="value.localColumns && !value.relationType"
                      label="Local Columns"
                      v-model="value.localColumns[0]"
                      :items="columns"
                      chips
                      :disabled="value.isLocked || isParentRelation"
                  />
                </v-card-text>
              </v-card>
            </v-flex>
            <v-flex pa-2 sm12 md6 lg4>
                <v-card>
                    <v-card-text>
                      <v-text-field
                          v-model="columnName"
                          :label="value.relationType === 'nm' ? 'Pseudo column to access the related records' : 'Column containing the id of the related record'"
                          @blur="saveColumnName($event.target.value)"
                          :rules="[v => (!allColumnsName.includes(v) || 'The name is taken.'), v => (v !== '' || 'Column should not be empty string')]"
                          :disabled="value.isLocked"
                      />
                      <v-text-field
                          v-if="value.relationType === 'nm'"
                          v-model="oppositeColumnName"
                          label="Pseudo column in related model to access the records in this model"
                          :disabled="value.isLocked"
                      />
                      <v-autocomplete
                          v-if="value.relationType === 'nm'"
                          label="Related model"
                          chips
                          v-model="value.relatedTable"
                          :items="availableTables"
                          :disabled="value.isLocked || !!value.relationType"
                      />
                      <v-autocomplete
                          v-if="value.relationType === '1n' || !value.relationType"
                          label="Related model"
                          chips
                          v-model="value.foreignTable"
                          :items="availableTables"
                          :disabled="value.isLocked || !!value.relationType"
                      />
                      <v-select
                          v-if="value.foreignColumns || !value.relationType"
                          label="Remote Column"
                          v-model="value.foreignColumns[0]"
                          :items="availableColumnsWithoutPseudoColumn"
                          chips
                          :disabled="value.isLocked || isParentRelation || value.relationType === '1n'"
                      />
                      <v-select
                          v-if="value.displayColumns && value.relationType"
                          label="Display column of the related model"
                          v-model="displayColumns[0]"
                          :items="availableColumns"
                          chips
                          :disabled="value.isLocked || isParentRelation"
                          clearable
                      />
                    </v-card-text>
                </v-card>
            </v-flex>
        </dis-simple-panel>
    </div>
</template>
<script>
import themeable from 'vuetify/lib/mixins/themeable'
export default {
  name: 'DataModelForeignKeyTemplate',
  mixins: [themeable],
  props: {
    value: {
      type: Object,
      required: true
    },
    columns: {
      type: Array,
      required: true
    },
    relationIndex: {
      type: Number
    },
    isParentRelation: {
      type: Boolean,
      default: false
    },
    allColumnsName: {
      type: Array
    }
  },
  data () {
    return {
      displayColumns: [],
      columnName: '',
      oppositeColumnName: ''
    }
  },
  created () {
    this.value.displayColumns = this.value.displayColumns === null ? [] : this.value.displayColumns
    this.displayColumns = [...this.value.displayColumns]
    this.columnName = this.value.localColumns[0]
    this.oppositeColumnName = this.value.oppositeColumnName
  },
  mounted () {
    this.$watch('displayColumns', function (val) {
      this.$emit('displayColumns', val, this.relationIndex, this.columnName)
    })
    this.$watch('oppositeColumnName', function (val, oldVal) {
      this.$emit('oppositeColumnName', val, oldVal, this.relationIndex)
    })
  },
  computed: {
    availableTables () {
      return this.$store.state.templates.summary.models.filter(item => item.isTableCreated).map(item => item.table)
    },
    availableColumns () {
      if (this.value.relatedTable) return Object.entries(this.$store.state.templates.summary.models.find(item => item.table === this.value.relatedTable).columns).map((item) => item[0])
      else if (this.value.foreignTable) return Object.entries(this.$store.state.templates.summary.models.find(item => item.table === this.value.foreignTable).columns).map((item) => item[0])
      return []
    },
    availableColumnsWithoutPseudoColumn () {
      if (this.value.relatedTable) return Object.entries(this.$store.state.templates.summary.models.find(item => item.table === this.value.relatedTable).columns).filter((item) => item[1].type !== 'pseudo').map((item) => item[0])
      else if (this.value.foreignTable) return Object.entries(this.$store.state.templates.summary.models.find(item => item.table === this.value.foreignTable).columns).filter((item) => item[1].type !== 'pseudo').map((item) => item[0])
      return []
    },
    relationTypes () {
      return [{ text: 'One-to-many', value: '1n' }, { text: 'Many-to-many', value: 'nm' }]
    }
  },
  methods: {
    saveColumnName (value) {
      if (value === '' || this.allColumnsName.includes(value)) {
        this.columnName = this.value.localColumns[0]
      } else {
        this.columnName = value
        this.$emit('columnName', this.columnName, this.value.localColumns[0], this.relationIndex)
      }
    }
  }
}
</script>
