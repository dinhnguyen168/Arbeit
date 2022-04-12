var activeDoc = activeDocument;

var cFilename = 'map.json';
var cPath = activeDocument.fullName.path + '/' + cFilename;
var f = new File(cPath);
if (f.open('w')) {
  f.writeln('{');

  for (var j = 0; j < documents.length; j++) {
    activeDocument = documents[j];
    var paths = getPaths();
    var doors = getDoors(paths);

    f.writeln('"paths": [')
    for (i = 0; i < paths.length; i++) {
      f.writeln(stringify(paths[i]) + (i < paths.length-1 ? ',' : ''));
    }
    f.writeln('],')

    f.writeln('"doors": [')
    for (i = 0; i < doors.length; i++) {
      f.writeln(stringify(doors[i]) + (i < doors.length-1 ? ',' : ''));
    }
    f.writeln(']')

  }
  f.writeln('}');
  f.close();

  alert('Datei "' + cPath + '" erzeugt.');
}

activeDocument = activeDoc;


function getPaths() {
  var paths = [];
  var pathItem = null;
  var layer = getLayer ('Paths');
  var correctPointDistance = 5;

  if (layer) {
    for (var i = 0; i < layer.pathItems.length; i++) {
      var item = layer.pathItems[i];
      var pathPoints = item.pathPoints;
      for (var j = 1; j < pathPoints.length; j++) {
        var point1 = pathPoints[j - 1];
        var point2 = pathPoints[j];
        pathItem = new PathItem(paths.length, point1, point2, item.name + (j > 1 ? '.' + j : ''));
        for (var k = 0; k < paths.length; k++) {
          compPath = paths[k];
          if (isSamePoint(compPath.start, pathItem.start, correctPointDistance))
            pathItem.start = compPath.start;
          else if (isSamePoint(compPath.end, pathItem.start, correctPointDistance))
            pathItem.start = compPath.end;

          if (isSamePoint(compPath.start, pathItem.end, correctPointDistance))
            pathItem.end = compPath.start;
          else if (isSamePoint(compPath.end, pathItem.end, correctPointDistance))
            pathItem.end = compPath.end;
        }
        paths.push(pathItem);
      }
    }
  } else
    alert('Ebene "Paths" nicht gefunden')

  this.connectPaths(paths);
  return paths
}

function connectPaths(paths) {
  for (var i = 0; i < paths.length; i++) {
    var pathItem = paths[i];
    pathItem.next = findPathIdsByPoint(paths, pathItem.end, pathItem.id);
    pathItem.prev = findPathIdsByPoint(paths, pathItem.start, pathItem.id);

    for (var j = 0; j < pathItem.next.length; j++) {
      var nextPathItem = paths[pathItem.next[j]]
      if ((pathItem.end.x !== nextPathItem.start.x || pathItem.end.y !== nextPathItem.start.y) &&
          (pathItem.end.x !== nextPathItem.end.x || pathItem.end.y !== nextPathItem.end.y)) {
        alert ('different points connecting paths ' + pathItem.name + ' and ' + nextPathItem.name)
      }
    }
  }
}

function findPathIdsByPoint(paths, point, ignoreId) {
  var maxDistance = 5;
  var foundPathIds = [];
  for (var j = 0; j < paths.length; j++) {
    var pathItem = paths[j];
    if (pathItem.id !== ignoreId) {
      if (isSamePoint(pathItem.start, point, maxDistance) || isSamePoint(paths[j].end, point, maxDistance)) {
        foundPathIds.push(pathItem.id);
      }
    }
  }
  return foundPathIds;
}

function isSamePoint (p1, p2, maxDistance) {
  var d2 = Math.pow(p1.x - p2.x, 2) + Math.pow(p1.y - p2.y, 2);
  return (d2 <= maxDistance * maxDistance);
}


function getDoors(paths) {
  var doors = [];
  var door = null;
  var doorsLayer = getLayer('Doors');
  if (doorsLayer) {
    var unassignedDoorIds = []
    for (var h = 0; h < doorsLayer.layers.length; h++) {
      var layer = doorsLayer.layers[h]
      var groupName = layer.name
      for (var i = 0; i < layer.pathItems.length; i++) {
        var item = layer.pathItems[i];
        var pathPoints = item.pathPoints;
        for (var j = 1; j < pathPoints.length; j++) {
          var point1 = pathPoints[j - 1];
          var point2 = pathPoints[j];
          door = new Door(doors.length, point1, point2, item.name, groupName);
          if (door.start) {
            var assigned = false
            for (var k = 0; k < paths.length; k++) {
              var pathItem = paths[k];
              if (pathItem.startDoor === null || pathItem.endDoor === null) {
                if (door.assignToPath(pathItem)) assigned = true;
              }
            }
            doors.push(door);
            if (!assigned) unassignedDoorIds.push(door.id);
          }
        }
      }
    }
    if (unassignedDoorIds.length > 0) alert('Doors unassigned: ' + stringify(unassignedDoorIds))
  } else
    alert('Ebene "Doors" nicht gefunden')

  for (var i = 0; i < doors.length; i++) {
    delete doors[i].center;
  }

  return doors;
}

function round(pos) {
  return Math.round(0 + pos);
}

function stringify (value) {
  var str = '';
  if (value === null)
    str = 'null';
  else if (typeof value === 'object' && value instanceof Array) {
    str += '[';
    for (var i=0; i<value.length; i++) {
      var strval = stringify(value[i]);
      if (strval > '') str += strval + (i < value.length - 1 ? ',' : '');
    }
    str += ']';
  } else if (typeof value === 'object') {
    str += '{';
    for (var key in value) {
      var strval = stringify(value[key]);
      if (strval > '') str += '"'+ key + '":' + strval + ',';
    }
    str = str.substr(0, str.length-1)
    str += '}';
  } else if (typeof value === 'string') {
    str += '"' + value + '"';
  } else if (typeof value !== 'function') {
    str += value;
  }
  return str;
}


function getLayer (cName) {
  for (var i=0; i<activeDocument.layers.length; i++) {
    if (activeDocument.layers[i].name == cName) {
      return (activeDocument.layers[i]);
    }
  }
  return (null);
}


function PathItem (id, point1, point2, name) {
  this.id = id;
  this.name = name
  if (point1.anchor[0] > 1 && point2.anchor[0] > 1) {
    this.start = {x: round(point1.anchor[0] * 10) / 10, y: round((activeDocument.height - point1.anchor[1]) * 10) / 10};
    this.end = {x: round(point2.anchor[0] * 10) / 10, y: round((activeDocument.height - point2.anchor[1])* 10) / 10};
    this.startDoor = null;
    this.endDoor = null;
    this.prev = [];
    this.next = [];
  }
}


function Door (id, point1, point2, name, groupName) {
  const open = (name.length == 0 || name.match(/open$/) !== null);
  this.id = id;
  this.name = name.replace(/[^a-zA-Z0-9]+open$/, '');
  this.group = groupName;
  if (point1.anchor[0] > 1 && point2.anchor[0] > 1) {
    this.start = {x: round(point1.anchor[0] * 10) / 10, y: round((activeDocument.height - point1.anchor[1]) * 10) / 10};
    this.end = {x: round(point2.anchor[0] * 10) / 10, y: round((activeDocument.height - point2.anchor[1])* 10) / 10};
    this.center = {x: round((this.start.x + this.end.x) / 2), y: round((this.start.y + this.end.y) / 2)};
    this.open = open;

    // extend Lengt
    var ext = 6;
    var dX = this.end.x - this.start.x;
    var dY = this.end.y - this.start.y;
    var len = Math.sqrt(dX*dX + dY*dY);
    var extX = Math.round(10 * ext * dX / len) / 10;
    var extY = Math.round(10 * ext * dY / len) / 10;
    this.end.x += extX;
    this.end.y += extY;
    this.start.x -= extX;
    this.start.y -= extY;
  }

  this.assignToPath = function(pathItem) {
    var maxCenterDistance = 15
    var assigned = false;
    if (pathItem.startDoor === null) {
      if (isSamePoint (pathItem.start, this.center, maxCenterDistance)) {
        pathItem.startDoor = this.id;
        assigned = true;
      }
    }

    if (pathItem.endDoor === null) {
      if (isSamePoint (pathItem.end, this.center, maxCenterDistance)) {
        pathItem.endDoor = this.id;
        assigned = true;
      }
    }
    return assigned;
  }
}
