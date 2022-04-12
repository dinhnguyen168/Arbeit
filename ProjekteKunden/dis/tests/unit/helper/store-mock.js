export const defaultTemplatesState = {
  'summary': {
    'modules': ['Archive', 'Core', 'Curation', 'Geology', 'Project', 'Sample'],
    'models': [
      {
        'name': 'Expedition',
        'module': 'Project',
        'table': 'project_expedition',
        'parentModel': 'ProjectProgram',
        'fullName': 'ProjectExpedition',
        'modifiedAt': 1596208081,
        'generatedAt': 1604059322,
        'isTableCreated': true,
        'tableGenerationTimestamp': 1604911780,
        'generatedFiles': [{
          'path': '/app/backend/models/base/BaseProjectExpedition.php',
          'modified': 1604911824
        }, {
          'path': '/app/backend/models/base/BaseProjectExpeditionSearch.php',
          'modified': 1604051455
        }, {
          'path': '/app/backend/models/ProjectExpedition.php',
          'modified': 1603896764
        }, { 'path': '/app/backend/models/ProjectExpeditionSearch.php', 'modified': 1603896765 }],
        'columns': ['id', 'program_id', 'name', 'expedition', 'project_location', 'acr', 'chief_scientist', 'start_date', 'end_date', 'type_of_drilling', 'comment', 'country', 'state', 'county', 'city', 'rock_classification', 'geological_age', 'moratorium_start', 'moratorium_end', 'location_description', 'funding_agency', 'repository', 'repository_contact', 'comment_2']
      },
      {
        'name': 'Hole',
        'module': 'Project',
        'table': 'project_hole',
        'parentModel': 'ProjectSite',
        'fullName': 'ProjectHole',
        'modifiedAt': 1603549931,
        'generatedAt': 1604059323,
        'isTableCreated': true,
        'tableGenerationTimestamp': 1604911781,
        'generatedFiles': [{
          'path': '/app/backend/models/base/BaseProjectHole.php',
          'modified': 1604911824
        }, {
          'path': '/app/backend/models/base/BaseProjectHoleSearch.php',
          'modified': 1604051455
        }, {
          'path': '/app/backend/models/ProjectHole.php',
          'modified': 1603896764
        }, { 'path': '/app/backend/models/ProjectHoleSearch.php', 'modified': 1603896765 }],
        'columns': ['id', 'site_id', 'hole', 'combined_id', 'latitude_dec', 'longitude_dec', 'ground_level', 'elevation_rig', 'direction', 'inclination', 'start_date', 'end_date', 'comments', 'core_depth_ccsf', 'core_depth_csf', 'drilling_depth_dsf', 'drilling_depth_drf', 'comments_2', 'igsn', 'ukbgs_hole_id', 'ukbgs_natlgrid', 'coordinate_system', 'methods_in_hole', 'comments_3']
      },
      {
        'name': 'Program',
        'module': 'Project',
        'table': 'project_program',
        'parentModel': null,
        'fullName': 'ProjectProgram',
        'modifiedAt': 1596208082,
        'generatedAt': 1604059321,
        'isTableCreated': true,
        'tableGenerationTimestamp': 1604911779,
        'generatedFiles': [{
          'path': '/app/backend/models/base/BaseProjectProgram.php',
          'modified': 1604911824
        }, {
          'path': '/app/backend/models/base/BaseProjectProgramSearch.php',
          'modified': 1604051455
        }, {
          'path': '/app/backend/models/ProjectProgram.php',
          'modified': 1603896765
        }, { 'path': '/app/backend/models/ProjectProgramSearch.php', 'modified': 1603896765 }],
        'columns': ['id', 'name', 'program', 'remarks']
      },
      {
        'name': 'Site',
        'module': 'Project',
        'table': 'project_site',
        'parentModel': 'ProjectExpedition',
        'fullName': 'ProjectSite',
        'modifiedAt': 1596208082,
        'generatedAt': 1604059322,
        'isTableCreated': true,
        'tableGenerationTimestamp': 1604911781,
        'generatedFiles': [{
          'path': '/app/backend/models/base/BaseProjectSite.php',
          'modified': 1604911824
        }, {
          'path': '/app/backend/models/base/BaseProjectSiteSearch.php',
          'modified': 1604051455
        }, {
          'path': '/app/backend/models/ProjectSite.php',
          'modified': 1603896764
        }, { 'path': '/app/backend/models/ProjectSiteSearch.php', 'modified': 1603896765 }],
        'columns': ['id', 'expedition_id', 'combined_id', 'site', 'name', 'date_start', 'date_end', 'comment', 'drilling_method', 'drilling_method_details', 'platform_type', 'platform_name', 'platform_description', 'platform_operator', 'bit_sizes']
      }
    ],
    'forms': [
      {
        'name': 'expedition',
        'dataModel': 'ProjectExpedition',
        'modifiedAt': 1589268657,
        'generatedAt': 1596208100,
        'generatedFiles': [{
          'path': '/app/backend/forms/ExpeditionForm.php',
          'modified': 1604051455
        }, {
          'path': '/app/backend/forms/ExpeditionFormSearch.php',
          'modified': 1603896766
        }, { 'path': '/app/src/forms/ExpeditionForm.vue.generated', 'modified': 1604051455 }],
        'customVueFile': null
      },
      {
        'name': 'hole',
        'dataModel': 'ProjectHole',
        'modifiedAt': 1603712563,
        'generatedAt': 1603712565,
        'generatedFiles': [{
          'path': '/app/backend/forms/HoleForm.php',
          'modified': 1604051455
        }, {
          'path': '/app/backend/forms/HoleFormSearch.php',
          'modified': 1603896766
        }, { 'path': '/app/src/forms/HoleForm.vue.generated', 'modified': 1604051455 }],
        'customVueFile': null
      },
      {
        'name': 'program',
        'dataModel': 'ProjectProgram',
        'modifiedAt': 1603547983,
        'generatedAt': 1603547985,
        'generatedFiles': [{
          'path': '/app/backend/forms/ProgramForm.php',
          'modified': 1603896766
        }, {
          'path': '/app/backend/forms/ProgramFormSearch.php',
          'modified': 1603896766
        }, { 'path': '/app/src/forms/ProgramForm.vue.generated', 'modified': 1603896767 }],
        'customVueFile': null
      },
      {
        'name': 'site',
        'dataModel': 'ProjectSite',
        'modifiedAt': 1589271601,
        'generatedAt': 1596208102,
        'generatedFiles': [{
          'path': '/app/backend/forms/SiteForm.php',
          'modified': 1604051455
        }, {
          'path': '/app/backend/forms/SiteFormSearch.php',
          'modified': 1603896766
        }, { 'path': '/app/src/forms/SiteForm.vue.generated', 'modified': 1604051455 }],
        'customVueFile': null
      }
    ]
  },
  'forms': [
    {
      'name': 'program',
      'dataModel': 'ProjectProgram',
      'fields': [{
        'name': 'name',
        'label': 'Program Name',
        'description': 'program name, max. 80 chars',
        'validators': [{ 'type': 'required' }, { 'type': 'string', 'min': null, 'max': null }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': '-group1',
        'order': 0
      }, {
        'name': 'program',
        'label': 'Program Shortcut',
        'description': 'Program acronym, max. 8 chars',
        'validators': [{ 'type': 'required' }, { 'type': 'string', 'min': null, 'max': null }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': '-group1',
        'order': 1
      }, {
        'name': 'remarks',
        'label': 'Remarks',
        'description': 'additional remarks, max.255 chars',
        'validators': [{ 'type': 'string' }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': '-group1',
        'order': 2
      }],
      'subForms': { 'expedition': { 'buttonLabel': 'expedition', 'url': '/forms/expedition-form', 'filter': [] } },
      'supForms': [],
      'createdAt': 1583830838,
      'modifiedAt': 1603547983,
      'generatedAt': 1603547985,
      'filterDataModels': [],
      'requiredFilters': []
    },
    {
      'name': 'expedition',
      'dataModel': 'ProjectExpedition',
      'fields': [{
        'name': 'name',
        'label': 'Expedition Name',
        'description': '',
        'validators': [{ 'type': 'required' }, { 'type': 'string' }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Expedition Details',
        'order': 0
      }, {
        'name': 'expedition',
        'label': 'Expedition Code',
        'description': '(ICDP: 4 digit number) ',
        'validators': [{ 'type': 'required' }, { 'type': 'string' }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Expedition Details',
        'order': 1
      }, {
        'name': 'acr',
        'label': 'Acronym',
        'description': 'Abbreviation of Project ',
        'validators': [{ 'type': 'required' }, { 'type': 'string' }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Expedition Details',
        'order': 2
      }, {
        'name': 'chief_scientist',
        'label': 'Chief Scientists',
        'description': 'Name of Principle Investigators',
        'validators': [{ 'type': 'required' }, { 'type': 'string' }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Expedition Details',
        'order': 3
      }, {
        'name': 'start_date',
        'label': 'Start of Expedition',
        'description': '',
        'validators': [{ 'type': 'required' }],
        'formInput': { 'type': 'datetime', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Expedition Details',
        'order': 4
      }, {
        'name': 'end_date',
        'label': 'End of Expedition',
        'description': 'date of expedition end',
        'validators': [],
        'formInput': { 'type': 'datetime', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Expedition Details',
        'order': 5
      }, {
        'name': 'type_of_drilling',
        'label': 'Type of Drilling',
        'description': 'Sea, Land and/or Lake',
        'validators': [{ 'type': 'required' }, { 'type': 'string', 'min': null, 'max': null }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Expedition Details',
        'order': 6
      }, {
        'name': 'comment',
        'label': 'Additional Information',
        'description': '',
        'validators': [{ 'type': 'string', 'min': null, 'max': null }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Expedition Details',
        'order': 7
      }, {
        'name': 'country',
        'label': 'Country',
        'description': 'Country in Which the Drilling Takes Place',
        'validators': [{ 'type': 'required' }, { 'type': 'string', 'min': null, 'max': null }],
        'formInput': {
          'type': 'select',
          'disabled': false,
          'calculate': '',
          'selectSource': { 'type': 'list', 'listName': 'COUNTRIES', 'textField': 'remark', 'valueField': 'display' },
          'allowFreeInput': false,
          'multiple': true,
          'jsCalculate': ''
        },
        'group': 'Expedition Location',
        'order': 0
      }, {
        'name': 'state',
        'label': 'State',
        'description': 'State in Which the Drilling Takes Place',
        'validators': [{ 'type': 'string' }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Expedition Location',
        'order': 1
      }, {
        'name': 'county',
        'label': 'County',
        'description': 'County in Which the Drilling Takes Place',
        'validators': [{ 'type': 'string' }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Expedition Location',
        'order': 2
      }, {
        'name': 'city',
        'label': 'City',
        'description': 'City Next to Drilling',
        'validators': [{ 'type': 'string', 'min': null, 'max': null }],
        'formInput': {
          'type': 'select',
          'disabled': false,
          'calculate': '',
          'selectSource': { 'type': 'list', 'listName': 'CITIES', 'textField': 'remark', 'valueField': 'display' },
          'allowFreeInput': false,
          'multiple': false,
          'jsCalculate': ''
        },
        'group': 'Expedition Location',
        'order': 3
      }, {
        'name': 'location_description',
        'label': 'Location Description',
        'description': '',
        'validators': [{ 'type': 'string', 'min': null, 'max': null }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Expedition Location',
        'order': 4
      }, {
        'name': 'geological_age',
        'label': 'Geological Age',
        'description': 'Multiple Choice is Possible',
        'validators': [{ 'type': 'required' }, { 'type': 'string', 'min': null, 'max': null }],
        'formInput': {
          'type': 'select',
          'disabled': false,
          'calculate': '',
          'selectSource': {
            'type': 'list',
            'listName': 'ctrl_geological_age',
            'textField': 'remark',
            'valueField': 'display'
          },
          'allowFreeInput': false,
          'multiple': true,
          'jsCalculate': ''
        },
        'group': 'Geological Details',
        'order': 0
      }, {
        'name': 'rock_classification',
        'label': 'Rock Classification',
        'description': 'Multiple Choice is Possible',
        'validators': [{ 'type': 'required' }, { 'type': 'string', 'min': null, 'max': null }],
        'formInput': {
          'type': 'select',
          'disabled': false,
          'calculate': '',
          'selectSource': {
            'type': 'list',
            'listName': 'Ctrl_rock_calssification',
            'textField': 'remark',
            'valueField': 'display'
          },
          'allowFreeInput': false,
          'multiple': true,
          'jsCalculate': ''
        },
        'group': 'Geological Details',
        'order': 1
      }, {
        'name': 'moratorium_start',
        'label': 'Moratorium Start',
        'description': '',
        'validators': [],
        'formInput': { 'type': 'date', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Moratorium Details',
        'order': 0
      }, {
        'name': 'moratorium_end',
        'label': 'Moratorium End',
        'description': '',
        'validators': [],
        'formInput': { 'type': 'date', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Moratorium Details',
        'order': 1
      }, {
        'name': 'funding_agency',
        'label': 'Funding Agency',
        'description': '',
        'validators': [{ 'type': 'string' }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Moratorium Details',
        'order': 2
      }, {
        'name': 'repository',
        'label': 'Repository',
        'description': '',
        'validators': [{ 'type': 'string', 'min': null, 'max': null }],
        'formInput': {
          'type': 'select',
          'disabled': false,
          'calculate': '',
          'selectSource': { 'type': 'list', 'listName': 'repository', 'textField': 'remark', 'valueField': 'display' },
          'allowFreeInput': true,
          'multiple': true,
          'jsCalculate': ''
        },
        'group': 'Repositories',
        'order': 0
      }, {
        'name': 'repository_contact',
        'label': 'Repository Contact',
        'description': '',
        'validators': [{ 'type': 'string' }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Repositories',
        'order': 1
      }, {
        'name': 'comment_2',
        'label': 'Additional Storage Information',
        'description': 'Which rocks are where?',
        'validators': [{ 'type': 'string', 'min': null, 'max': null }],
        'formInput': { 'type': 'textarea', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Repositories',
        'order': 2
      }],
      'subForms': {
        'site': {
          'buttonLabel': 'site',
          'url': '/forms/site-form',
          'filter': [{ 'unit': 'expedition', 'fromField': 'id' }]
        }
      },
      'supForms': {
        'program': {
          'buttonLabel': 'program',
          'url': '/forms/program-form',
          'parentIdField': 'program_id',
          'filter': []
        }
      },
      'createdAt': 1583842993,
      'modifiedAt': 1589268657,
      'generatedAt': 1596208100,
      'filterDataModels': { 'program': { 'model': 'ProjectProgram', 'value': 'id', 'text': 'program', 'ref': 'program_id' } },
      'requiredFilters': [{ 'value': 'program', 'as': 'program_id' }]
    },
    {
      'name': 'site',
      'dataModel': 'ProjectSite',
      'fields': [{
        'name': 'combined_id',
        'label': 'Combined Id ',
        'description': '(Only for viewing; fills automatically when saved)',
        'validators': [{ 'type': 'string', 'min': null, 'max': null }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Site Details',
        'order': 0
      }, {
        'name': 'site',
        'label': 'Site Number',
        'description': 'Number or Abbreviation',
        'validators': [{ 'type': 'string', 'min': null, 'max': null }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Site Details',
        'order': 1
      }, {
        'name': 'name',
        'label': 'Name of Site',
        'description': '(if any)',
        'validators': [{ 'type': 'string', 'min': null, 'max': null }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Site Details',
        'order': 2
      }, {
        'name': 'date_start',
        'label': 'Start Date',
        'description': '',
        'validators': [{ 'type': 'required' }],
        'formInput': { 'type': 'datetime', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Site Details',
        'order': 3
      }, {
        'name': 'date_end',
        'label': 'End Date',
        'description': '',
        'validators': [],
        'formInput': { 'type': 'datetime', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Site Details',
        'order': 4
      }, {
        'name': 'comment',
        'label': 'Additional Information',
        'description': '',
        'validators': [{ 'type': 'string' }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Site Details',
        'order': 5
      }, {
        'name': 'drilling_method',
        'label': 'Drilling Method',
        'description': '',
        'validators': [{ 'type': 'string', 'min': null, 'max': null }],
        'formInput': {
          'type': 'select',
          'disabled': false,
          'calculate': '',
          'selectSource': {
            'type': 'list',
            'listName': 'ctrl_collection_method',
            'textField': 'remark',
            'valueField': 'display'
          },
          'allowFreeInput': false,
          'multiple': true,
          'jsCalculate': ''
        },
        'group': 'Drilling Details',
        'order': 0
      }, {
        'name': 'bit_sizes',
        'label': 'Bit Sizes',
        'description': '',
        'validators': [{ 'type': 'string', 'min': null, 'max': null }],
        'formInput': {
          'type': 'select',
          'disabled': false,
          'calculate': '',
          'selectSource': { 'type': 'list', 'listName': 'bit_size', 'textField': 'remark', 'valueField': 'display' },
          'allowFreeInput': false,
          'multiple': true,
          'jsCalculate': ''
        },
        'group': 'Drilling Details',
        'order': 1
      }, {
        'name': 'drilling_method_details',
        'label': 'Drilling Method Details',
        'description': '',
        'validators': [{ 'type': 'string', 'min': null, 'max': null }],
        'formInput': {
          'type': 'text',
          'disabled': false,
          'calculate': "[drilling_method] +', '+ [bit_sizes]",
          'jsCalculate': "this.formModel['drilling_method'] +', '+ this.formModel['bit_sizes']"
        },
        'group': 'Drilling Details',
        'order': 2
      }, {
        'name': 'platform_type',
        'label': 'Platform Type',
        'description': '',
        'validators': [{ 'type': 'string', 'min': null, 'max': null }],
        'formInput': {
          'type': 'select',
          'disabled': false,
          'calculate': '',
          'selectSource': { 'type': 'list', 'listName': 'PLATFORM', 'textField': 'remark', 'valueField': 'display' },
          'allowFreeInput': false,
          'multiple': false,
          'jsCalculate': ''
        },
        'group': 'Platform Details',
        'order': 0
      }, {
        'name': 'platform_name',
        'label': 'Platform Name',
        'description': '',
        'validators': [{ 'type': 'string' }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Platform Details',
        'order': 1
      }, {
        'name': 'platform_description',
        'label': 'Platform Description',
        'description': '',
        'validators': [{ 'type': 'string' }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Platform Details',
        'order': 2
      }, {
        'name': 'platform_operator',
        'label': 'Platform Operator',
        'description': '',
        'validators': [{ 'type': 'string' }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Platform Details',
        'order': 3
      }],
      'subForms': {
        'hole': {
          'buttonLabel': 'hole',
          'url': '/forms/hole-form',
          'filter': [{ 'unit': 'expedition', 'fromField': 'expedition_id' }, { 'unit': 'site', 'fromField': 'id' }]
        }
      },
      'supForms': {
        'expedition': {
          'buttonLabel': 'expedition',
          'url': '/forms/expedition-form',
          'parentIdField': 'expedition_id',
          'filter': []
        }
      },
      'createdAt': 1583843766,
      'modifiedAt': 1589271601,
      'generatedAt': 1596208102,
      'filterDataModels': {
        'expedition': {
          'model': 'ProjectExpedition',
          'value': 'id',
          'text': 'acr',
          'ref': 'expedition_id'
        }
      },
      'requiredFilters': [{ 'value': 'expedition', 'as': 'expedition_id' }]
    },
    {
      'name': 'hole',
      'dataModel': 'ProjectHole',
      'fields': [{
        'name': 'hole',
        'label': 'Hole',
        'description': 'Hole Identifier (ICDP: one character A - Z)',
        'validators': [{ 'type': 'string', 'min': null, 'max': null }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Wellhole Details',
        'order': 0
      }, {
        'name': 'combined_id',
        'label': 'Combined Id ',
        'description': '(Only for viewing; automatically filled when saved)',
        'validators': [{ 'type': 'string', 'min': null, 'max': null }],
        'formInput': { 'type': 'text', 'disabled': true, 'calculate': '', 'jsCalculate': '' },
        'group': 'Wellhole Details',
        'order': 1
      }, {
        'name': 'latitude_dec',
        'label': 'Latitude (decimal degrees)',
        'description': '',
        'validators': [{ 'type': 'required' }, { 'type': 'number', 'min': -90, 'max': 90 }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Wellhole Details',
        'order': 2
      }, {
        'name': 'longitude_dec',
        'label': 'Longitude (decimal degrees)',
        'description': '',
        'validators': [{ 'type': 'required' }, { 'type': 'number', 'min': -180, 'max': 180 }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Wellhole Details',
        'order': 3
      }, {
        'name': 'coordinate_system',
        'label': 'Coordinate System',
        'description': '',
        'validators': [{ 'type': 'required' }, { 'type': 'string', 'min': null, 'max': null }],
        'formInput': { 'type': 'text', 'disabled': true, 'calculate': '', 'jsCalculate': '' },
        'group': 'Wellhole Details',
        'order': 4
      }, {
        'name': 'ground_level',
        'label': 'Ground Level [m]',
        'description': 'Height above Sea Level',
        'validators': [{ 'type': 'required' }, { 'type': 'number', 'min': null, 'max': null }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Wellhole Details',
        'order': 5
      }, {
        'name': 'elevation_rig',
        'label': 'Elevation of Rig Floor [m]',
        'description': 'Height of Rig Floor/Drillers Ref. Point above Ground Level',
        'validators': [{ 'type': 'required' }, { 'type': 'number', 'min': null, 'max': null }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Wellhole Details',
        'order': 6
      }, {
        'name': 'start_date',
        'label': 'Start Date',
        'description': 'Start of Drilling Operations',
        'validators': [{ 'type': 'required' }],
        'formInput': { 'type': 'datetime', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Wellhole Details',
        'order': 7
      }, {
        'name': 'end_date',
        'label': 'End Date',
        'description': 'End of Drilling Operations',
        'validators': [],
        'formInput': { 'type': 'datetime', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Wellhole Details',
        'order': 8
      }, {
        'name': 'comments',
        'label': 'Additional Information',
        'description': 'E.G. Name of Hole',
        'validators': [{ 'type': 'string', 'min': null, 'max': null }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Wellhole Details',
        'order': 9
      }, {
        'name': 'drilling_depth_drf',
        'label': 'Drill Depth Below Rig Floor [mbs]',
        'description': ' Depth Below Rig Floor/Drillers Reference Point, measured',
        'validators': [{ 'type': 'number', 'min': null, 'max': null }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Methods Depth Corrections',
        'order': 0
      }, {
        'name': 'drilling_depth_dsf',
        'label': 'Drill Depth below surface/ground level [mbs]',
        'description': 'Calculated from DRF - rig elevation',
        'validators': [{ 'type': 'number', 'min': null, 'max': null }],
        'formInput': {
          'type': 'text',
          'disabled': true,
          'calculate': '= [drilling_depth_drf] -[elevation_rig]',
          'jsCalculate': " this.formModel['drilling_depth_drf'] -this.formModel['elevation_rig']"
        },
        'group': 'Methods Depth Corrections',
        'order': 1
      }, {
        'name': 'core_depth_ccsf',
        'label': 'Core Compensation Depth below surface (CCSF)',
        'description': 'Corrected Core length from core and logging data',
        'validators': [{ 'type': 'number', 'min': null, 'max': null }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Methods Depth Corrections',
        'order': 2
      }, {
        'name': 'direction',
        'label': 'Direction of Inclination',
        'description': '',
        'validators': [{ 'type': 'string', 'min': null, 'max': null }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Methods Depth Corrections',
        'order': 3
      }, {
        'name': 'inclination',
        'label': 'Dip',
        'description': 'Degree of Inclination',
        'validators': [{ 'type': 'number', 'min': null, 'max': null }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Methods Depth Corrections',
        'order': 4
      }, {
        'name': 'comments_2',
        'label': 'Additional Information',
        'description': '',
        'validators': [{ 'type': 'string', 'min': null, 'max': null }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Methods Depth Corrections',
        'order': 5
      }, {
        'name': 'igsn',
        'label': 'IGSN',
        'description': '(Only for viewing; automatically filled when saved)',
        'validators': [{ 'type': 'string', 'min': null, 'max': null }],
        'formInput': { 'type': 'text', 'disabled': true, 'calculate': '', 'jsCalculate': '' },
        'group': 'Identifiers',
        'order': 0
      }, {
        'name': 'ukbgs_hole_id',
        'label': 'UK-BGS Hole ID',
        'description': 'UK-BGS Wellhole Identifier',
        'validators': [{ 'type': 'string' }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Identifiers',
        'order': 1
      }, {
        'name': 'ukbgs_natlgrid',
        'label': 'UK-BGS Grid',
        'description': 'UK-BGS National Grid Number',
        'validators': [{ 'type': 'string' }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Identifiers',
        'order': 2
      }, {
        'name': 'comments_3',
        'label': 'Additional Information',
        'description': '',
        'validators': [{ 'type': 'string', 'min': null, 'max': null }],
        'formInput': { 'type': 'text', 'disabled': false, 'calculate': '', 'jsCalculate': '' },
        'group': 'Identifiers',
        'order': 3
      }, {
        'name': 'methods_in_hole',
        'label': 'Methods In Hole',
        'description': 'Measurements Done in Hole',
        'validators': [{ 'type': 'string', 'min': null, 'max': null }],
        'formInput': {
          'type': 'select',
          'disabled': false,
          'calculate': '',
          'selectSource': { 'type': 'list', 'listName': 'MEASUREMENT', 'textField': 'remark', 'valueField': 'display' },
          'allowFreeInput': false,
          'multiple': true,
          'jsCalculate': ''
        },
        'group': 'Measurements',
        'order': 0
      }],
      'subForms': {
        'core': {
          'buttonLabel': 'core',
          'url': '/forms/core-form',
          'filter': [{ 'unit': 'expedition', 'fromField': 'expedition_id' }, {
            'unit': 'site',
            'fromField': 'site_id'
          }, { 'unit': 'hole', 'fromField': 'id' }]
        }
      },
      'supForms': {
        'site': {
          'buttonLabel': 'site',
          'url': '/forms/site-form',
          'parentIdField': 'site_id',
          'filter': [{ 'unit': 'expedition', 'fromField': 'expedition_id' }]
        }
      },
      'createdAt': 1583845728,
      'modifiedAt': 1603712563,
      'generatedAt': 1603712565,
      'filterDataModels': {
        'expedition': {
          'model': 'ProjectExpedition',
          'value': 'id',
          'text': 'acr',
          'ref': 'expedition_id'
        },
        'site': {
          'model': 'ProjectSite',
          'value': 'id',
          'text': 'site',
          'ref': 'site_id',
          'require': { 'value': 'expedition', 'as': 'expedition_id' }
        }
      },
      'requiredFilters': [{ 'value': 'site', 'as': 'site_id' }]
    }
  ],
  'models': [
    {
      'module': 'Project',
      'name': 'DrillingOverview',
      'table': 'project_drilling_overview',
      'importTable': null,
      'parentModel': 'ProjectSite',
      'columns': {
        'id': {
          'name': 'id',
          'importSource': '',
          'type': 'integer',
          'size': 11,
          'required': false,
          'primaryKey': true,
          'autoInc': true,
          'label': 'ID',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'site_id': {
          'name': 'site_id',
          'importSource': '',
          'type': 'integer',
          'size': 11,
          'required': true,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Site',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        }
      },
      'indices': { 'pk_id': { 'name': 'pk_id', 'type': 'PRIMARY', 'columns': ['id'] } },
      'relations': {
        'project_drilling_overview__project_site__parent': {
          'name': 'project_drilling_overview__project_site__parent',
          'relationType': 'parent',
          'foreignTable': 'project_site',
          'localColumns': ['site_id'],
          'foreignColumns': ['id']
        }
      },
      'behaviors': [],
      'createdAt': 1603566151,
      'modifiedAt': 1603566157,
      'generatedAt': 1604059323,
      'fullName': 'ProjectDrillingOverview'
    },
    {
      'module': 'Project',
      'name': 'Program',
      'table': 'project_program',
      'importTable': 'EXP_PROJECT_PROGRAM',
      'parentModel': null,
      'columns': {
        'id': {
          'name': 'id',
          'importSource': '',
          'type': 'integer',
          'size': 11,
          'required': false,
          'primaryKey': true,
          'autoInc': true,
          'label': 'Id',
          'description': 'auto incremented id',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'name': {
          'name': 'name',
          'importSource': 'NAME',
          'type': 'string',
          'size': 80,
          'required': true,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Program Name',
          'description': 'program name, max. 80 chars',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'program': {
          'name': 'program',
          'importSource': 'PROGRAM',
          'type': 'string',
          'size': 8,
          'required': true,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Program Shortcut',
          'description': 'program shortcut, max. 8 chars',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'remarks': {
          'name': 'remarks',
          'importSource': 'REMARKS',
          'type': 'string',
          'size': 255,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Remarks',
          'description': 'additional remarks, max.255 chars',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        }
      },
      'indices': { 'id': { 'name': 'id', 'type': 'PRIMARY', 'columns': ['id'] } },
      'relations': [],
      'behaviors': [],
      'createdAt': 1549089261,
      'modifiedAt': 1596208082,
      'generatedAt': 1604059321,
      'fullName': 'ProjectProgram'
    },
    {
      'module': 'Project',
      'name': 'Expedition',
      'table': 'project_expedition',
      'importTable': 'EXP_PROJECT_EXPEDITION',
      'parentModel': 'ProjectProgram',
      'columns': {
        'id': {
          'name': 'id',
          'importSource': '',
          'type': 'integer',
          'size': 11,
          'required': false,
          'primaryKey': true,
          'autoInc': true,
          'label': 'Id',
          'description': 'auto incremented id',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'program_id': {
          'name': 'program_id',
          'importSource': 'return function($aImportedRecord) {\n//IMPORTCOLUMN:PROGRAM;\n$cParentFilterValue = $aImportedRecord["PROGRAM"]; \nreturn \\app\\dis_migration\\Module::lookupValue("project_program", "id", ["program" => $cParentFilterValue]);\n};',
          'type': 'integer',
          'size': 11,
          'required': true,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Program Id',
          'description': 'parent id (of table project_program)',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'name': {
          'name': 'name',
          'importSource': 'NAME',
          'type': 'string',
          'size': 80,
          'required': true,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Expedition Name',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'expedition': {
          'name': 'expedition',
          'importSource': 'EXPEDITION',
          'type': 'string',
          'size': 64,
          'required': true,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Expedition Code',
          'description': '(ICDP: 4 digit number)',
          'validator': '',
          'validatorMessage': 'expedition code (Int. 1 - 99999), required key field, please enter / select a valid value',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'project_location': {
          'name': 'project_location',
          'importSource': '',
          'type': 'string',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Project Location',
          'description': 'Country/Location of Drilling Operation ',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'acr': {
          'name': 'acr',
          'importSource': 'ACR',
          'type': 'string',
          'size': 20,
          'required': true,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Acronym',
          'description': 'Abbreviation of Project ',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'chief_scientist': {
          'name': 'chief_scientist',
          'importSource': 'CHIEF_SCIENTIST',
          'type': 'string',
          'size': '',
          'required': true,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Chief Scientists',
          'description': 'Name of Principle Investigators',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'start_date': {
          'name': 'start_date',
          'importSource': 'START_DATE',
          'type': 'dateTime',
          'size': 0,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Start of Expedition',
          'description': '',
          'validator': '',
          'validatorMessage': 'expedition start (Date UTC), required field, please enter a valid value',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'end_date': {
          'name': 'end_date',
          'importSource': 'END_DATE',
          'type': 'dateTime',
          'size': 0,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'End of Expedition',
          'description': 'date of expedition end',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'type_of_drilling': {
          'name': 'type_of_drilling',
          'importSource': '',
          'type': 'string',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Type of Drilling',
          'description': 'Sea, Land and/or Lake',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'comment': {
          'name': 'comment',
          'importSource': 'COMMENT',
          'type': 'string',
          'size': '',
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Additional Information',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'country': {
          'name': 'country',
          'importSource': '',
          'type': 'string_multiple',
          'size': null,
          'required': true,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Country',
          'description': 'Country in Which the Drilling Takes Place',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'state': {
          'name': 'state',
          'importSource': '',
          'type': 'string',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'State',
          'description': 'State in Which the Drilling Takes Place',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'county': {
          'name': 'county',
          'importSource': '',
          'type': 'string',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'County',
          'description': 'County in Which the Drilling Takes Place',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'city': {
          'name': 'city',
          'importSource': '',
          'type': 'string',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'City',
          'description': 'City Next to Drilling',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'rock_classification': {
          'name': 'rock_classification',
          'importSource': '',
          'type': 'string_multiple',
          'size': null,
          'required': true,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Rock Classification',
          'description': 'Drilled Rock Types',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'geological_age': {
          'name': 'geological_age',
          'importSource': '',
          'type': 'string_multiple',
          'size': null,
          'required': true,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Geological Age',
          'description': 'Age of Drilled Rocks',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'moratorium_start': {
          'name': 'moratorium_start',
          'importSource': '',
          'type': 'date',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Moratorium Start',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'moratorium_end': {
          'name': 'moratorium_end',
          'importSource': '',
          'type': 'date',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Moratorium End',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'location_description': {
          'name': 'location_description',
          'importSource': '',
          'type': 'string',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Location Description',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'funding_agency': {
          'name': 'funding_agency',
          'importSource': '',
          'type': 'string',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Funding Agency',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'repository': {
          'name': 'repository',
          'importSource': '',
          'type': 'string_multiple',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Repository',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'repository_contact': {
          'name': 'repository_contact',
          'importSource': '',
          'type': 'string',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Repository Contact',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'comment_2': {
          'name': 'comment_2',
          'importSource': '',
          'type': 'string',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Comment 2',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        }
      },
      'indices': {
        'id': { 'name': 'id', 'type': 'PRIMARY', 'columns': ['id'] },
        'program_id': { 'name': 'program_id', 'type': 'KEY', 'columns': ['program_id'] },
        'program_id__expedition': {
          'name': 'program_id__expedition',
          'type': 'UNIQUE',
          'columns': ['program_id', 'expedition']
        }
      },
      'relations': {
        'project_expedition__project_program__parent': {
          'name': 'project_expedition__project_program__parent',
          'relationType': 'parent',
          'foreignTable': 'project_program',
          'localColumns': ['program_id'],
          'foreignColumns': ['id']
        }
      },
      'behaviors': [],
      'createdAt': 1583841012,
      'modifiedAt': 1596208081,
      'generatedAt': 1604059322,
      'fullName': 'ProjectExpedition'
    },
    {
      'module': 'Project',
      'name': 'Site',
      'table': 'project_site',
      'importTable': 'EXP_PROJECT_SITE',
      'parentModel': 'ProjectExpedition',
      'columns': {
        'id': {
          'name': 'id',
          'importSource': 'SKEY',
          'type': 'integer',
          'size': 11,
          'required': false,
          'primaryKey': true,
          'autoInc': true,
          'label': '#',
          'description': '#; auto incremented id',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'expedition_id': {
          'name': 'expedition_id',
          'importSource': 'return function($aImportedRecord) {\n$cParentFilterValue = preg_replace("/_[^_]+$/", "", $aImportedRecord["combined_id"]); \nreturn \\app\\dis_migration\\Module::lookupValue("project_expedition", "id", ["expedition" => $cParentFilterValue]);\n};',
          'type': 'integer',
          'size': 11,
          'required': true,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Expedition Id',
          'description': 'parent id (of table project_expedition)',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'combined_id': {
          'name': 'combined_id',
          'importSource': "LTRIM(CAST([EXPEDITION] AS varchar)) + '_' + LTRIM(CAST([SITE] AS varchar))",
          'type': 'string',
          'size': 8,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Combined Id',
          'description': 'CombinedKey: expedition, site (Only for viewing)',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'site': {
          'name': 'site',
          'importSource': 'SITE',
          'type': 'string',
          'size': 64,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Site Number',
          'description': '',
          'validator': '<>0',
          'validatorMessage': 'site number (Int. 1 - 99999), required key field, please enter / select a valid value',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'name': {
          'name': 'name',
          'importSource': 'NAME',
          'type': 'string',
          'size': '',
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Name of Site',
          'description': '(if any)',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'date_start': {
          'name': 'date_start',
          'importSource': 'DATE_START',
          'type': 'dateTime',
          'size': 0,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Start Date',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'date_end': {
          'name': 'date_end',
          'importSource': 'DATE_END',
          'type': 'dateTime',
          'size': 0,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'End Date',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'comment': {
          'name': 'comment',
          'importSource': '',
          'type': 'string',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Additional Information',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'drilling_method': {
          'name': 'drilling_method',
          'importSource': '',
          'type': 'string_multiple',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Drilling Method',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'drilling_method_details': {
          'name': 'drilling_method_details',
          'importSource': '',
          'type': 'string',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Drilling Method Details',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'platform_type': {
          'name': 'platform_type',
          'importSource': '',
          'type': 'string',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Platform Type',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'platform_name': {
          'name': 'platform_name',
          'importSource': '',
          'type': 'string',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Platform Name',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'platform_description': {
          'name': 'platform_description',
          'importSource': '',
          'type': 'string',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Platform Description',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'platform_operator': {
          'name': 'platform_operator',
          'importSource': '',
          'type': 'string',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Platform Operator',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'bit_sizes': {
          'name': 'bit_sizes',
          'importSource': '',
          'type': 'string_multiple',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Bit Sizes',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        }
      },
      'indices': {
        'id': { 'name': 'id', 'type': 'PRIMARY', 'columns': ['id'] },
        'expedition_id': { 'name': 'expedition_id', 'type': 'KEY', 'columns': ['expedition_id'] },
        'expedition_id__site': { 'name': 'expedition_id__site', 'type': 'UNIQUE', 'columns': ['expedition_id', 'site'] }
      },
      'relations': {
        'project_site__project_expedition__parent': {
          'name': 'project_site__project_expedition__parent',
          'relationType': 'parent',
          'foreignTable': 'project_expedition',
          'localColumns': ['expedition_id'],
          'foreignColumns': ['id']
        }
      },
      'behaviors': [{
        'behaviorClass': 'app\\behaviors\\template\\UniqueCombinationAutoIncrementBehavior',
        'parameters': { 'searchFields': ['expedition_id'], 'fieldToFill': 'site', 'useAlphabet': false }
      }],
      'createdAt': 1583843094,
      'modifiedAt': 1596208082,
      'generatedAt': 1604059322,
      'fullName': 'ProjectSite'
    },
    {
      'module': 'Project',
      'name': 'Hole',
      'table': 'project_hole',
      'importTable': 'EXP_PROJECT_HOLE',
      'parentModel': 'ProjectSite',
      'columns': {
        'id': {
          'name': 'id',
          'importSource': 'SKEY',
          'type': 'integer',
          'size': 11,
          'required': false,
          'primaryKey': true,
          'autoInc': true,
          'label': 'SKEY',
          'description': 'SKEY; auto incremented id',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'site_id': {
          'name': 'site_id',
          'importSource': 'return function($aImportedRecord) {\n$cParentFilterValue = preg_replace("/_[^_]+$/", "", $aImportedRecord["combined_id"]); \nreturn \\app\\dis_migration\\Module::lookupValue("project_site", "id", ["combined_id" => $cParentFilterValue]);\n};',
          'type': 'integer',
          'size': 11,
          'required': true,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Site Id',
          'description': 'parent id (of table project_site)',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'hole': {
          'name': 'hole',
          'importSource': 'HOLE',
          'type': 'string',
          'size': 64,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Hole',
          'description': 'Hole Identifier (ICDP: one character A - Z)',
          'validator': "LIKE ( '[A-Z]')",
          'validatorMessage': 'hole code (max. 1 chars, A-Z), reqiured key field, please enter / select a valid value',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'combined_id': {
          'name': 'combined_id',
          'importSource': "LTRIM(CAST([EXPEDITION] AS varchar)) + '_' + LTRIM(CAST([SITE] AS varchar)) + '_' + LTRIM([HOLE])",
          'type': 'string',
          'size': '',
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Combined Id ',
          'description': '(Only for viewing; automatically filled when saved)',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'latitude_dec': {
          'name': 'latitude_dec',
          'importSource': 'LATITUDE_DEC',
          'type': 'double',
          'size': 0,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Latitude (decimal degrees)',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'longitude_dec': {
          'name': 'longitude_dec',
          'importSource': '',
          'type': 'double',
          'size': 0,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Longitude (decimal degrees)',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'ground_level': {
          'name': 'ground_level',
          'importSource': '',
          'type': 'double',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Ground Level [m]',
          'description': 'Height above Sea Level',
          'validator': '',
          'validatorMessage': '',
          'unit': 'm',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'elevation_rig': {
          'name': 'elevation_rig',
          'importSource': '',
          'type': 'double',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Elevation of Rig Floor [m]',
          'description': 'Height of Rig Floor above Ground Level/surface',
          'validator': '',
          'validatorMessage': '',
          'unit': 'm',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'direction': {
          'name': 'direction',
          'importSource': 'DIRECTION',
          'type': 'string',
          'size': 0,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Direction of Inclination',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'inclination': {
          'name': 'inclination',
          'importSource': 'INCLINATION',
          'type': 'double',
          'size': 0,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Dip',
          'description': 'Degree of Inclination',
          'validator': '',
          'validatorMessage': '',
          'unit': 'degrees',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'start_date': {
          'name': 'start_date',
          'importSource': 'START_DATE',
          'type': 'dateTime',
          'size': 0,
          'required': true,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Start Date',
          'description': 'Start of Drilling Operations',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'end_date': {
          'name': 'end_date',
          'importSource': 'END_DATE',
          'type': 'dateTime',
          'size': 0,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'End Date',
          'description': 'End of Drilling Operations',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'comments': {
          'name': 'comments',
          'importSource': '',
          'type': 'string',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Additional Information',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'core_depth_ccsf': {
          'name': 'core_depth_ccsf',
          'importSource': 'CORE_DEPTH_CCSF',
          'type': 'double',
          'size': 0,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Core Composite Depth below surface (CCSF) [mbs]',
          'description': 'Corrected total core length from core data & logging data',
          'validator': '>0',
          'validatorMessage': '',
          'unit': 'm',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'core_depth_csf': {
          'name': 'core_depth_csf',
          'importSource': 'CORE_DEPTH_CSF',
          'type': 'double',
          'size': 0,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Core Depth (CSF) [mbs]',
          'description': 'Total Length of Drilled Core ',
          'validator': '>0',
          'validatorMessage': '',
          'unit': 'm',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'drilling_depth_dsf': {
          'name': 'drilling_depth_dsf',
          'importSource': 'DRILLING_DEPTH_DSF',
          'type': 'double',
          'size': 0,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'DSF: Drilled Depth Below Surface [mbs]',
          'description': '[mbs] > 0',
          'validator': '>0',
          'validatorMessage': '',
          'unit': 'm',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'drilling_depth_drf': {
          'name': 'drilling_depth_drf',
          'importSource': 'DRILLING_DEPTH_DRF',
          'type': 'double',
          'size': 0,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'DRF: Drilled Depth Below Rig Floor [mbrf]',
          'description': '[mbrf] > 0',
          'validator': '>0',
          'validatorMessage': '',
          'unit': 'm',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'comments_2': {
          'name': 'comments_2',
          'importSource': '',
          'type': 'string',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Additional Information',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'igsn': {
          'name': 'igsn',
          'importSource': 'IGSN',
          'type': 'string',
          'size': 15,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'IGSN',
          'description': '(Only for viewing; automatically filled when saved)',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': 'H',
          'pseudoCalc': ''
        },
        'ukbgs_hole_id': {
          'name': 'ukbgs_hole_id',
          'importSource': '',
          'type': 'string',
          'size': 50,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'UK-BGS Hole ID',
          'description': 'UK-BGS Wellhole Identifier',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'ukbgs_natlgrid': {
          'name': 'ukbgs_natlgrid',
          'importSource': '',
          'type': 'string',
          'size': 50,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'UK-BGS Grid',
          'description': 'UK-BGS National Grid Number',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'coordinate_system': {
          'name': 'coordinate_system',
          'importSource': '',
          'type': 'string',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Coordinate System',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': 'WGS84',
          'pseudoCalc': ''
        },
        'methods_in_hole': {
          'name': 'methods_in_hole',
          'importSource': '',
          'type': 'string_multiple',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Methods In Hole',
          'description': 'Measurements Done in Hole',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        },
        'comments_3': {
          'name': 'comments_3',
          'importSource': '',
          'type': 'string_multiple',
          'size': null,
          'required': false,
          'primaryKey': false,
          'autoInc': false,
          'label': 'Comments 3',
          'description': '',
          'validator': '',
          'validatorMessage': '',
          'unit': '',
          'selectListName': '',
          'calculate': '',
          'defaultValue': '',
          'pseudoCalc': ''
        }
      },
      'indices': {
        'id': { 'name': 'id', 'type': 'PRIMARY', 'columns': ['id'] },
        'site_id': { 'name': 'site_id', 'type': 'KEY', 'columns': ['site_id'] },
        'site_id__hole': { 'name': 'site_id__hole', 'type': 'UNIQUE', 'columns': ['site_id', 'hole'] }
      },
      'relations': {
        'project_hole__project_site__parent': {
          'name': 'project_hole__project_site__parent',
          'relationType': 'parent',
          'foreignTable': 'project_site',
          'localColumns': ['site_id'],
          'foreignColumns': ['id']
        }
      },
      'behaviors': [{
        'behaviorClass': 'app\\behaviors\\template\\UniqueCombinationAutoIncrementBehavior',
        'parameters': { 'searchFields': ['site_id'], 'fieldToFill': 'hole', 'useAlphabet': true }
      }],
      'createdAt': 1583844047,
      'modifiedAt': 1603549931,
      'generatedAt': 1604059323,
      'fullName': 'ProjectHole'
    }
  ],
  'behaviors': [
    {
      'behaviorClass': 'app\\behaviors\\template\\CumulativeSectionLengthBehavior',
      'name': 'Cumulative section length',
      'parameters': [{
        'name': 'minRelativeLength',
        'hint': 'The lower cut-off value for core validation (ratio, between 0.8 and 1.0).'
      }, {
        'name': 'maxRelativeLength',
        'hint': 'The upper cut-off value for core validation (ratio, between 1.0 and 1.2).'
      }]
    },
    {
      'behaviorClass': 'app\\behaviors\\template\\DefaultFromParentBehavior',
      'name': 'Default from Parent',
      'parameters': [{
        'name': 'parentSourceColumn',
        'hint': 'The parent column that contains the value you want to copy'
      }, { 'name': 'destinationColumn', 'hint': 'the column name that holds the copied value' }]
    },
    {
      'behaviorClass': 'app\\behaviors\\template\\DefaultFromSiblingBehavior',
      'name': 'Default from Sibling',
      'parameters': [{
        'name': 'parentRefColumn',
        'hint': 'The column that contains the id of the parent record'
      }, {
        'name': 'sourceColumn',
        'hint': 'The sibling column that contains the value you want to copy'
      }, { 'name': 'destinationColumn', 'hint': 'the column name that holds the copied value' }]
    },
    {
      'behaviorClass': 'app\\behaviors\\template\\DefaultFromSiblingUnitsBehavior',
      'name': 'Default from Sibling Units',
      'parameters': [{
        'name': 'unitsRelationName',
        'hint': 'The name of the relation from section to unit. See definitions (@) in BaseCoreSection.php.'
      }, {
        'name': 'positionOnSectionColumnName',
        'hint': 'Name of the column that holds the position measurement on the core section.'
      }]
    },
    {
      'behaviorClass': 'app\\behaviors\\template\\SiblingsLimitBehavior',
      'name': 'Siblings Limit',
      'parameters': [{
        'name': 'parentRefColumn',
        'hint': 'The column that contains the id of the parent record'
      }, { 'name': 'limit', 'hint': 'Maximum number of sibling records' }]
    },
    {
      'behaviorClass': 'app\\behaviors\\template\\SiblingsLimitFromParentBehavior',
      'name': 'Children Limit From Parent',
      'parameters': [{
        'name': 'parentRefColumn',
        'hint': 'The column that contains the id of the parent record'
      }, { 'name': 'parentSourceColumn', 'hint': 'The column in parent that has the limit value' }]
    },
    {
      'behaviorClass': 'app\\behaviors\\template\\SplittableSectionBehavior',
      'name': 'Splittable Section',
      'parameters': [{ 'name': 'splitsModel', 'hint': 'The table where the splits will be saved e.g. CoreSectionSplit' }]
    },
    {
      'behaviorClass': 'app\\behaviors\\template\\UniqueCombinationAutoIncrementBehavior',
      'name': 'Unique Combination Auto Increment',
      'parameters': [{
        'name': 'searchFields',
        'hint': 'columns names (comma separated) that build the group in which a unique value should be created'
      }, { 'name': 'fieldToFill', 'hint': 'column name to be filled by the calculated value' }, {
        'name': 'useAlphabet',
        'hint': 'use A-Z values for auto increment? (y/n)'
      }]
    },
    {
      'behaviorClass': 'app\\behaviors\\template\\ValidateSplitOriginTypeBehavior',
      'name': 'Validate Origin Split Type Value',
      'parameters': []
    }
  ],
  'modelsFilterString': ''
}
