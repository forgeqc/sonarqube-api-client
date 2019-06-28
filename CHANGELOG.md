# Changelog

## Version 1.2.0
- Added metric aggregation for a project porfolio. Aggregation algorithms implement (https://docs.sonarqube.org/latest/user-guide/portfolios/) sonarqube aggregation logic.
- Added capability to request metrics for multiple sonarqube projects.
- Added capabilities to test if a sonarqube user exists and to update user name & email.

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
