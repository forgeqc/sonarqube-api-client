# Changelog

## Version 1.1.0
 - Updated default measures list to match sonarqube / sonarcloud main dashboard metrics.
 - Added capability to request metrics for a user defined metricKeys list.

## Version 1.0.0
- Initial release
- SonarqubeInstance :
  - getProjects()
  - createGroup()
  - deleteGroup()
  - createUser()
  - deactivateUser()
- SonarqubeProject :
  - exists()
  - create()
  - getProperties()
  - getMeasures()
  - getMeasuresHistory().
  - addGroupPermission()
  - removeGroupPermission()
  - addUserPermission()
  - removeUserPermission()
