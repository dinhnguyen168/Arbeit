<template>
  <v-form>
    <v-layout row wrap>
      <v-flex sm12 md4 lg3>
        <v-card>
          <v-form>
            <v-card-text>
              <v-text-field v-model="module" disabled label="Tableset"/>
              <v-text-field @input="fixModelNameCase" v-model="name" label="Data model name" :disabled="scenario === 'update'"/>
              <v-text-field v-model="table" label="Database table" readonly disabled/>
              <v-select
                  ref="parentSelectInput"
                  v-model="parentModel"
                  :disabled="!name"
                  label="Parent data model"
                  :items="$store.state.templates.summary.models"
                  item-text="fullName"
                  clearable/>
            </v-card-text>
          </v-form>
          <v-btn :to="`/settings/templates-manager/` + (model ? `forms/${model.fullName}` : '')" :disabled="!model || model.generatedFiles.filter(item => item.modified).length !== model.generatedFiles.length" small color="teal">
            manage forms
          </v-btn>
        </v-card>
      </v-flex>
      <v-flex sm12 md8 lg9>
        <v-card class="mb-2">
          <v-card-title>
            <h2>Columns</h2>
          </v-card-title>
          <v-card-text>
            <v-btn class="mb-3" @click="addColumn">Add Column</v-btn>
            <draggable :force-fallback="true"  v-model="columns" v-bind="{group: 'columns', handle: '.drag-handle', ghostClass: 'u-drag-ghost'}" tag="ul" class="c-data-model-template__columns">
              <li v-for="(column, index) in columns" :key="column.name">
                                    <data-model-column-template v-model="columns[index]"
                                                                @remove="() => removeColumn(index)"
                                                                :allColumnsName="columns.map(col => col.name).filter(colName => colName !== column.name)"
                                                                :isFKColumn="!!parentModel && column.name === parentModelColumnName"
                                                                :parentModelColumnName ="parentModelColumnName"
                                    />
              </li>
            </draggable>
          </v-card-text>
        </v-card>
        <v-card class="mb-2">
          <v-card-title>
            <h2>Indices</h2>
          </v-card-title>
          <v-card-text>
            <v-btn class="mb-3" @click="addIndex">Add Index</v-btn>
            <ul class="c-data-model-template__indices">
              <li v-for="(DBIndex, index) in indices" :key="DBIndex.name">
                                <data-model-index-template v-model="indices[index]"
                                                           @remove="() => removeIndex(index)"
                                                           :columns="columns.map(item => item.name)"
                                                           :parentModelColumnName ="parentModelColumnName"
                                />
              </li>
            </ul>
          </v-card-text>
        </v-card>
        <v-card class="mb-2">
          <v-card-title>
            <h2>Relations</h2>
          </v-card-title>
          <v-card-text>
            <v-btn class="mb-3" @click.stop="relationDialog = true">
              Add Relation
            </v-btn>
            <v-dialog
                max-width="600"
                v-model="relationDialog"
            >
              <v-card>
                <v-card-title class="text-h5">
                  <h1 style="font-weight: normal">Relations</h1>
                </v-card-title>
                <v-card-text>
                  <v-select
                      v-model="relationType"
                      label="Relation type"
                      :items="relationTypes"
                      chips
                      clearable/>

                  <v-select
                      v-model="relatedModel"
                      label="Related model"
                      :items="relatedModels"
                      item-text="fullName"
                      chips
                      clearable/>
                  <v-select
                      v-model="displayColumn"
                      label="Display column of the related model"
                      :items="oneToManyAvailableColumns"
                      chips
                      v-if=" relationType === '1n' || relationType === 'nm'"
                      clearable/>
                  <v-text-field v-model="columnName" :label="relationType === 'nm' ? 'Pseudo column to access the related records' : 'Column containing the id of the related record'"/>
                  <v-text-field v-model="oppositeColumnName" label="Pseudo column in related model to access the records in this model" v-if="relationType === 'nm'"/>
                </v-card-text>
                <v-card-actions>
                  <v-spacer></v-spacer>
                  <v-btn text @click="relationDialog = false">
                    Cancer
                  </v-btn>
                  <v-btn text @click="addRelation">
                    Ok
                  </v-btn>
                </v-card-actions>
              </v-card>
            </v-dialog>
            <ul class="c-data-model-template__indices">
              <li v-for="(relation, index) in relations" :key="relation.name">
                <data-model-foreign-key-template
                  v-model="relations[index]"
                  @remove="() => removeRelation(index)"
                  :allColumnsName="columns.map(col => col.name).filter(colName => colName !== relation.localColumns[0])"
                  :isParentRelation="parentModelObject && relations[index].foreignTable === parentModelObject.table && relations[index].localColumns.length === 1 && relations[index].localColumns[0] === parentModelColumnName"
                  :columns="columns.map(item => item.name)"
                  :relationIndex="index"
                  @displayColumns="updateDisplayColumns"
                  @columnName="updateColumnName"
                  @oppositeColumnName="updateOppositeColumnName"
                />
              </li>
            </ul>
          </v-card-text>
        </v-card>
        <v-card class="mb-2">
          <v-card-title>
            <h2>Behaviors</h2>
          </v-card-title>
          <v-card-text>
            <v-menu offset-y :close-on-content-click="false" v-model="behaviorsMenu">
              <template v-slot:activator="{ on }">
                <v-btn ref="behaviorAddButton" class="mb-3" v-on="on">ADD</v-btn>
              </template>
              <v-list ref="behaviorsList">
                <v-list-tile>
                  <v-text-field ref="behaviorFilterInput" placeholder="Filter" hide-details v-model="behaviorsFilter"></v-text-field>
                </v-list-tile>
                <v-list-tile v-for="(item, index) in behaviorsList" :key="index" @click="() => addBehavior(item)">
                  <v-list-tile-title>{{ item.name }}</v-list-tile-title>
                </v-list-tile>
              </v-list>
            </v-menu>
            <ul class="c-data-model-template__behaviors">
              <draggable :force-fallback="true"  v-model="behaviors" v-bind="{group: 'behaviors', handle: '.drag-handle', ghostClass: 'u-drag-ghost'}" tag="ul" class="c-data-model-template__columns">
                <li v-for="(behavior, index) in behaviors" :key="index">
                  <data-model-behavior-template v-model="behaviors[index]" @remove="() => removeBehavior(index)" :columns="columns.map(item => item.name)"/>
                </li>
              </draggable>
            </ul>
          </v-card-text>
        </v-card>
        <v-card class="mb-2">
          <v-card-text>
            <v-btn class="mb-3" @click="() => saveModelTemplate(false)" color="success" :loading="loading" :disabled="scenario === 'update'">Save</v-btn>
            <v-btn class="mb-3" @click="() => saveModelTemplate(true)" color="blue" :loading="loading" :disabled="scenario === 'create'">Save & Generate</v-btn>
            <v-alert v-model="showErrorSummary" dismissible>
              <ul>
                <li v-for="error in serverValidationErrors" :key="error.field">
                  <strong>{{error.field}}</strong>: {{error.message}}
                </li>
              </ul>
            </v-alert>
            <v-alert v-model="showGenerationLog" dismissible color="info">
              <ul>
                <li v-for="(log, index) in generationLog" :key="index">
                  {{log}}
                </li>
              </ul>
            </v-alert>
            <auto-code-generator v-if="generatorAttributes" ref="autoCodeGenerator" v-model="generationDialog" :generatorAttributes="generatorAttributes" generatorId="dis-model"></auto-code-generator>
          </v-card-text>
        </v-card>
      </v-flex>
    </v-layout>
  </v-form>
</template>

<script>
import draggable from 'vuedraggable'
import DataModelColumnTemplate from './DataModelColumnTemplate'
import DataModelIndexTemplate from './DataModelIndexTemplate'
import DataModelForeignKeyTemplate from './DataModelForeignKeyTemplate'
import DataModelBehaviorTemplate from './DataModelBehaviorTemplate'
import AutoCodeGenerator from '../AutoCodeGenerator'
import { snakeCase, upperFirst, camelCase, startCase, lowerCase } from 'lodash'

export default {
  name: 'DataModelTemplate',
  components: { AutoCodeGenerator, DataModelForeignKeyTemplate, DataModelBehaviorTemplate, DataModelIndexTemplate, DataModelColumnTemplate, draggable },
  props: {
    initialTemplate: {
      type: Object,
      required: true
    },
    scenario: {
      type: String,
      required: true
    }
  },
  data () {
    return {
      module: '',
      name: '',
      parentModel: '',
      columns: [],
      indices: [],
      behaviors: [],
      serverValidationErrors: [],
      generationLog: [],
      generationDialog: false,
      loading: false,
      behaviorsFilter: '',
      behaviorsMenu: false,
      relations: [],
      relatedModel: '',
      relationType: '',
      relationDialog: false,
      displayColumn: '',
      columnName: '',
      oppositeColumnName: ''
    }
  },
  mounted () {
    this.module = this.initialTemplate.module
    this.name = this.initialTemplate.name
    this.parentModel = this.initialTemplate.parentModel
    this.columns = this.initialTemplate.columns
    this.indices = this.initialTemplate.indices
    this.relations = this.initialTemplate.relations
    this.behaviors = this.initialTemplate.behaviors
    // add watcher for parentModel
    // this watcher is added after loading all template values to avoid triggering the watcher while loading the initial template values
    this.$watch('parentModel', function (val, oldVal) {
      // set relation
      if (oldVal) {
        const isParentSelfReference = upperFirst(camelCase(`${this.module}-${this.name}`)) === oldVal
        let oldModel = this.$store.state.templates.summary.models.find(item => item.fullName === oldVal)
        this.removeColumn(this.columns.findIndex(item => item.name === snakeCase(`${isParentSelfReference ? 'parent' : oldModel.name} id`)))
        this.removeIndex(this.indices.findIndex(item => item.name === snakeCase(`${isParentSelfReference ? 'parent' : oldModel.name} id`)))
        this.removeIndex(this.indices.findIndex(item => item.columns[0] === snakeCase(`${isParentSelfReference ? 'parent' : oldModel.name} id`)))
        this.removeRelation(this.relations.findIndex(item => item.foreignTable === (isParentSelfReference ? this.table : oldModel.table) &&
            item.localColumns.length === 1 &&
            item.localColumns[0] === snakeCase(`${isParentSelfReference ? 'parent' : oldModel.name} id`)))
      }
      if (val) {
        const isParentSelfReference = upperFirst(camelCase(`${this.module}-${this.name}`)) === this.parentModel
        const newModel = this.$store.state.templates.summary.models.find(item => item.fullName === val)
        this.columns.push({
          name: this.parentModelColumnName,
          importSource: '',
          type: 'integer',
          size: 11,
          required: !isParentSelfReference,
          primaryKey: false,
          autoInc: false,
          label: isParentSelfReference ? 'parent' : newModel.name,
          description: '',
          validator: '',
          validatorMessage: '',
          unit: '',
          selectListName: '',
          calculate: '',
          defaultValue: ''
        })
        this.indices.push({
          columns: [snakeCase(`${isParentSelfReference ? 'parent' : newModel.name} id`)],
          isLocked: null,
          name: snakeCase(`${isParentSelfReference ? 'parent' : newModel.name} id`),
          type: 'KEY'
        })
        this.relations.unshift({
          name: `${this.table}__${newModel.table}__parent`,
          foreignTable: isParentSelfReference ? this.table : newModel.table,
          localColumns: [this.parentModelColumnName],
          foreignColumns: ['id'],
          displayColumns: null
        })
      }
    })
  },
  watch: {
    relationDialog () {
      this.relationType = ''
      this.relatedModel = ''
      this.displayColumn = ''
      this.columnName = ''
      this.oppositeColumnName = ''
    }
  },
  computed: {
    showErrorSummary: {
      get: function () {
        return this.serverValidationErrors.length > 0
      },
      set: function () {
        this.serverValidationErrors = []
      }
    },
    showGenerationLog: {
      get: function () {
        return this.generationLog.length > 0
      },
      set: function () {
        this.generationLog = []
      }
    },
    generatorAttributes () {
      return { templateName: this.$route.params.modelFullName }
    },
    table () {
      return this.name ? snakeCase(`${this.module} ${this.name}`) : ''
    },
    parentModelObject () {
      return this.$store.state.templates.summary.models.find(item => item.fullName === this.parentModel)
    },
    parentModelColumnName () {
      const isParentSelfReference = upperFirst(camelCase(`${this.module}-${this.name}`)) === this.parentModel
      return this.parentModelObject ? snakeCase(`${isParentSelfReference ? 'parent' : this.parentModelObject.name} id`) : ''
    },
    relatedModelObject () {
      return this.$store.state.templates.summary.models.find(item => item.fullName === this.relatedModel)
    },
    oneToManyAvailableColumns () {
      if (this.relatedModel) return Object.entries(this.$store.state.templates.summary.models.find(item => item.fullName === this.relatedModel).columns).map((item) => item[0])
      return []
    },
    relatedModelColumnName () {
      let relatedModelColumnName = ''
      if (this.relatedModelObject) {
        relatedModelColumnName = this.relationType === 'nm'
          ? snakeCase(`${this.relatedModelObject.table}_ids_nm`)
          : snakeCase(`${this.relatedModelObject.table}_${this.displayColumn}_ids`) + '_1n'
      }
      return relatedModelColumnName
    },
    behaviorsList () {
      if (!this.behaviorsFilter) {
        return this.$store.state.templates.behaviors
      } else {
        return this.$store.state.templates.behaviors.filter(item => item.name.toLowerCase().indexOf(this.behaviorsFilter.toLowerCase()) >= 0)
      }
    },
    model () {
      let fullName = upperFirst(this.module) + upperFirst(this.name)
      return this.$store.state.templates.summary.models.find(item => item.fullName === fullName)
    },
    relatedModels () {
      // let existingModels = this.relations.map(item => item.relatedTable)
      // return this.$store.state.templates.summary.models.filter(item => !existingModels.includes(item.table))
      return this.$store.state.templates.summary.models
    },
    relationTypes () {
      return [{ text: 'One-to-many', value: '1n' }, { text: 'Many-to-many', value: 'nm' }]
    }
  },
  methods: {
    async addColumn () {
      let columnName = await this.$dialog.prompt({
        title: 'Column Name',
        text: 'Enter a unique column name'
      })
      this.addColumnWithColumnName(columnName)
    },
    addColumnWithColumnName (columnName) {
      let name = columnName ? snakeCase(columnName) : null
      if (name && this.columns.findIndex(item => item.name === name) < 0) {
        this.columns.push({
          name: name,
          importSource: '',
          type: 'integer',
          size: null,
          required: false,
          primaryKey: false,
          autoInc: false,
          label: startCase(columnName),
          description: '',
          validator: '',
          validatorMessage: '',
          unit: '',
          selectListName: '',
          calculate: '',
          defaultValue: '',
          newColumnAdded: true
        })
      }
    },
    removeColumn (index) {
      if (index > -1) {
        this.columns.splice(index, 1)
      }
    },
    async addIndex () {
      let indexName = await this.$dialog.prompt({
        title: 'Index Name',
        text: 'enter the desired index name'
      })
      let name = snakeCase(indexName)
      if (name && this.indices.findIndex(item => item.name === name) < 0) {
        this.indices.push({
          name: name,
          type: '',
          columns: []
        })
      }
    },
    removeIndex (index) {
      if (index > -1) {
        this.indices.splice(index, 1)
      }
    },
    addRelation () {
      if (this.relationType && this.relatedModel) {
        let name = snakeCase(this.relatedModel)
        let typeName = lowerCase(this.relationType).replace(/\s/g, '')
        /*
         * relationType noch hinzufügen?
         */
        if (name && this.relations.findIndex(item => item.name === name) < 0) {
          const isRelationSelfReference = upperFirst(camelCase(`${this.module}-${this.name}`)) === this.relatedModel
          const newModel = this.$store.state.templates.summary.models.find(item => item.fullName === this.relatedModel)
          if (typeName === 'nm') {
            this.relations.push({
              name: `${this.table}__${newModel.table}__${this.columnName}__${typeName}`,
              relatedTable: isRelationSelfReference ? this.table : newModel.table,
              relationType: typeName,
              localColumns: [this.columnName],
              foreignColumns: null,
              displayColumns: [this.displayColumn],
              oppositeColumnName: this.oppositeColumnName
            })
          } else {
            this.relations.push({
              name: `${this.table}__${newModel.table}__${this.columnName}__${typeName}`,
              foreignTable: isRelationSelfReference ? this.table : newModel.table,
              relationType: typeName,
              localColumns: [this.columnName],
              foreignColumns: ['id'],
              displayColumns: [this.displayColumn]
            })
          }
          if (this.relatedModel && !!this.relationType) {
            this.columns.push({
              name: this.columnName,
              importSource: '',
              type: this.relationType === 'nm' ? snakeCase('many to many') : snakeCase('one to many'),
              size: null,
              required: false,
              primaryKey: false,
              autoInc: false,
              label: isRelationSelfReference ? 'self' : newModel.name,
              description: '',
              validator: '',
              validatorMessage: '',
              unit: '',
              selectListName: '',
              calculate: '',
              defaultValue: '',
              searchable: false,
              displayColumn: this.displayColumn,
              relatedTable: newModel.table
            })
          }
          if (this.relationType === '1n') {
            this.indices.push({
              name: this.columnName,
              type: 'KEY',
              columns: [this.columnName],
              isLocked: true
            })
          }
          this.relationDialog = false // Need to place at the end of block
        }
      }
    },
    updateDisplayColumns (val, relationIndex, columnName) {
      this.relations[relationIndex].displayColumns = [...val]
      if (this.relations[relationIndex].relationType === 'nm') {
        this.columns.find(item => item.name === columnName).displayColumn = val[0]
        this.relations[relationIndex].name = `${this.table}__${this.relations[relationIndex].relatedTable}__${columnName}__${this.relations[relationIndex].relationType}`
      }
      if (this.relations[relationIndex].relationType === '1n') {
        this.columns.find(item => item.name === columnName).displayColumn = val[0]
        this.relations[relationIndex].name = `${this.table}__${this.relations[relationIndex].foreignTable}__${columnName}__${this.relations[relationIndex].relationType}`
      }
    },
    updateColumnName (val, oldVal, relationIndex) {
      this.columns.find(item => item.name === oldVal).name = val
      if (this.relations[relationIndex].relationType === 'nm') {
        this.relations[relationIndex].name = `${this.table}__${this.relations[relationIndex].relatedTable}__${val}__${this.relations[relationIndex].relationType}`
        this.relations[relationIndex].localColumns = [val]
      }
      if (this.relations[relationIndex].relationType === '1n') {
        this.indices.find(item => item.name === oldVal).name = val
        this.indices.find(item => item.name === val).columns = [val]
        this.relations[relationIndex].name = `${this.table}__${this.relations[relationIndex].foreignTable}__${val}__${this.relations[relationIndex].relationType}`
        this.relations[relationIndex].localColumns = [val]
      }
    },
    updateOppositeColumnName (val, oldVal, relationIndex) {
      if (this.relations[relationIndex].relationType === 'nm') {
        this.relations[relationIndex].oppositeColumnName = val
      }
    },
    removeRelation (index) {
      let relation = this.relations[index]
      if (index > -1) {
        let columnIndex = this.columns.findIndex(item => item.displayColumn === relation.displayColumns[0])
        if (columnIndex > -1) {
          let colName = this.columns[columnIndex].name
          this.removeColumn(columnIndex)
          if (relation.relationType === '1n') {
            let indicesIndex = this.indices.findIndex(item => item.name === colName)
            if (indicesIndex) this.removeIndex(indicesIndex)
          }
        }
        this.relations.splice(index, 1)
      }
    },
    async addBehavior (behavior) {
      let params = {}
      behavior.parameters.forEach(item => {
        params[item.name] = ''
      })
      this.behaviors.push({
        behaviorClass: behavior.behaviorClass,
        parameters: params
      })
      this.behaviorsFilter = ''
      this.behaviorsMenu = false
    },
    removeBehavior (index) {
      if (index > -1) {
        this.behaviors.splice(index, 1)
      }
    },
    async saveModelTemplate (generate = false) {
      let model = {
        module: this.module,
        name: upperFirst(this.name),
        table: this.table,
        parentModel: this.parentModel,
        columns: {},
        indices: {},
        relations: {},
        behaviors: []
      }
      this.columns.forEach(column => {
        model.columns[column.name] = column
      })
      this.indices.forEach(index => {
        index.columns = index.columns.filter(item => Object.keys(model.columns).includes(item))
        model.indices[index.name] = index
      })
      this.relations.forEach(relation => {
        if (relation.relationType !== 'nm') relation.localColumns = relation.localColumns.filter(item => Object.keys(model.columns).includes(item))
        model.relations[relation.name] = relation
      })
      this.behaviors.forEach(behavior => {
        model.behaviors.push({
          behaviorClass: behavior.behaviorClass,
          parameters: behavior.parameters
        })
      })
      if (generate) {
        await this.saveAndGenerate(model)
      } else {
        await this.save(model)
      }
    },
    async save (model) {
      this.serverValidationErrors = []
      this.loading = true
      try {
        if (this.scenario === 'update') {
          await this.$store.dispatch('templates/updateModelTemplate', { fullName: upperFirst(camelCase(`${model.module}-${model.name}`)), template: model })
          await this.$dialog.message.success('saved successfully', {
            position: 'bottom'
          })
        } else if (this.scenario === 'create') {
          let result = await this.$store.dispatch('templates/createModelTemplate', model)
          await this.$dialog.message.success('created successfully', {
            position: 'bottom'
          })
          await this.$router.push(`/settings/templates-manager/data-models/update/${result.fullName}`)
        } else {
          throw Error('DataModelTemplate: invalid scenario ' + this.scenario)
        }
        return true
      } catch (e) {
        if (e.response && e.response.status === 422) {
          this.serverValidationErrors = e.response.data
        } else {
          throw e
        }
      } finally {
        this.loading = false
      }
    },
    async saveAndGenerate (model) {
      const saved = await this.save(model)
      this.generationLog.push(`Step 1/3: saving the template`)
      if (saved) {
        this.generationLog.push(`template was saved successfully`)
        this.loading = true
        try {
          // create table
          this.generationLog.push(`Step 2/3: creating table`)
          const created = await this.$store.dispatch('templates/createModelTable', upperFirst(camelCase(`${model.module}-${model.name}`)))
          if (created) {
            this.generationLog.push(`table was created successfully`)
            this.generationLog.push(`Step 3/3: generating classes`)
            this.generationDialog = true
            await this.$nextTick()
            const result = await this.$refs.autoCodeGenerator.generate()
            if (result === 'generated') {
              this.generationLog.push(`classes were generated successfully`)
            } else {
              this.generationLog.push(`some files will be overwritten. choose how you want to continue.`)
            }
          } else {
            this.generationLog.push(`was not able to create/re-create the table`)
          }
        } catch (e) {
          await this.$dialog.notify.warning(e.message, { timeout: 30000 })
          console.log(e)
        } finally {
          this.loading = false
        }
      } else {
        this.generationLog.push(`was not able to save the template`)
      }
    },
    fixModelNameCase () {
      this.name = upperFirst(camelCase(this.name))
    }
  }
}
</script>

<style scoped lang="stylus">
    .c-data-model-template
        &__columns, &__indices, &__behaviors
            list-style none
            padding 0
            margin 0
            li
                margin-bottom 10px
</style>
