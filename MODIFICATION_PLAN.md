# Plan de Modificări - Clarificare Roluri Director/Manager

## Situația Actuală vs. Situația Dorită

### **Director (Level 80)**
**Acum:**
- Poate avea `region_id = NULL` (super user - vede toate regiunile)
- Vede toate regiunile dacă e super user
- Poate realoca resurse

**Dorit:**
- **Obligatoriu** să aibă `region_id` (director regional)
- Vede **doar resursele** din regiunea lui
- Nu mai poate fi super user

### **Manager (Level 50)**
**Acum:**
- Folosește `region_id` din user pentru filtrare
- Vede contractele din regiunea lui

**Dorit:**
- Nu mai folosește `region_id` din user pentru filtrare
- Vede **doar contractele asignate** lui (prin `contracts.manager_id`)
- Executanții pe care îi vede sunt din **regiunea contractului** (nu a lui)

---

## Fișiere de Modificat

### **1. Services (8 fișiere)**
- ✅ `app/Services/ContractManagementService.php` - filtrare contracte
- ✅ `app/Services/TaskManagementService.php` - permisiuni task-uri
- ✅ `app/Services/SubdivisionManagementService.php` - permisiuni subdiviziuni
- ✅ `app/Services/UserManagementService.php` - filtrare utilizatori
- ✅ `app/Services/DashboardService.php` - date dashboard
- ✅ `app/Services/ReportsService.php` - filtrare rapoarte
- ⚠️ `app/Services/TaskService.php` - notificări (verificare)
- ⚠️ `app/Services/NotificationService.php` - verificare

### **2. Controllers (2-3 fișiere)**
- ⚠️ `app/Controllers/UserController.php` - validare region_id pentru directori
- ⚠️ `app/Controllers/ContractController.php` - verificare dacă există validări
- ⚠️ `app/Controllers/DashboardController.php` - verificare filtrare

### **3. Models (1-2 fișiere)**
- ⚠️ `app/Models/UserModel.php` - verificare metode helper
- ⚠️ `app/Models/ContractModel.php` - verificare metode helper

### **4. Views (2-3 fișiere)**
- ⚠️ `app/Views/users/create.php` - region_id obligatoriu pentru directori
- ⚠️ `app/Views/users/edit.php` - region_id obligatoriu pentru directori
- ⚠️ `app/Views/dashboard/manager.php` - verificare dacă afișează corect

---

## Detalii Modificări pe Fișier

### **ContractManagementService.php**
**Modificări:**
1. `getViewableContracts()` - Manager folosește `getContractsForManagerWithDetails()` (deja OK)
2. `canCreateContract()` - Elimina verificarea super user pentru director
3. `canEditContract()` - Elimina verificarea super user pentru director
4. `getAllowedRegionsForCreate()` - Director trebuie să aibă region_id obligatoriu

**Impact:** MEDIU

### **TaskManagementService.php**
**Modificări:**
1. `canViewTask()` - Manager verifică `contract.manager_id` (nu `region_id`)
2. `canEditTask()` - Manager verifică `contract.manager_id`
3. `canDeleteTask()` - Manager verifică `contract.manager_id`
4. `getAllowedSubdivisionsForCreate()` - Manager folosește contractele asignate
5. `getAllowedUsersForAssignment()` - Manager folosește regiunea contractului

**Impact:** MARE

### **SubdivisionManagementService.php**
**Modificări:**
1. `canViewSubdivision()` - Manager verifică `contract.manager_id`
2. `canEditSubdivision()` - Manager verifică `contract.manager_id`
3. `canDeleteSubdivision()` - Manager verifică `contract.manager_id`
4. `getAllowedContractsForCreate()` - Manager folosește contractele asignate

**Impact:** MEDIU

### **UserManagementService.php**
**Modificări:**
1. `getViewableUsers()` - Manager vede executanții din regiunea contractelor
2. `getAllowedUsersForAssignment()` - Manager vede executanții din regiunea contractelor
3. Elimina verificările super user pentru director

**Impact:** MEDIU

### **DashboardService.php**
**Modificări:**
1. `getRegionsForDashboard()` - Director nu mai poate vedea toate regiunile
2. `getContractsForDashboard()` - Manager folosește contractele asignate
3. `getTeamWorkload()` - Manager folosește regiunea contractelor
4. `getTasksPerRegionChart()` - Manager filtrează pe contractele asignate
5. `getCriticalBlockers()` - Manager filtrează pe contractele asignate
6. `getUpcomingDeadlines()` - Manager filtrează pe contractele asignate

**Impact:** MARE

### **ReportsService.php**
**Modificări:**
1. Toate rapoartele - Director filtrează strict pe region_id
2. Elimina verificările super user

**Impact:** MEDIU

### **UserController.php**
**Modificări:**
1. Validare `region_id` obligatoriu pentru directori (create/edit)
2. Elimina posibilitatea de a crea director fără region_id

**Impact:** MIC

---

## Rata de Succes Estimată

### **Complexitate: MEDIE-ALTA** ⚠️

**Riscuri identificate:**
1. ⚠️ **Locații multiple** - logica de filtrare e distribuită în multe servicii
2. ⚠️ **Dependențe între servicii** - modificările pot afecta alte funcționalități
3. ✅ **Schema pregătită** - baza de date deja suportă structura necesară
4. ✅ **Metode existente** - `getContractsForManagerWithDetails()` există deja

### **Estimare:**
- **Fișiere de modificat:** ~12-15
- **Locații de modificat:** ~50-60
- **Complexitate:** 7/10
- **Timp estimat:** 2-3 ore de dezvoltare + 1-2 ore de testare
- **Rata de succes:** 85-90% (datorită testelor necesare)

---

## Plan de Testare (CRITIC!)

### **1. Testare Director Regional**
- [ ] Login ca Director cu region_id setat
- [ ] Verifică că vede doar regiunea lui în dashboard
- [ ] Verifică că vede doar contractele din regiunea lui
- [ ] Verifică că poate crea contracte doar în regiunea lui
- [ ] Verifică că poate edita contracte doar din regiunea lui
- [ ] Verifică că vede doar utilizatorii din regiunea lui
- [ ] Verifică că rapoartele sunt filtrate pe regiunea lui
- [ ] Verifică că NU poate fi creat fără region_id

### **2. Testare Manager Contract**
- [ ] Login ca Manager (fără region_id sau cu region_id diferit)
- [ ] Verifică că vede doar contractele asignate lui (manager_id)
- [ ] Verifică că poate crea task-uri doar pentru contractele lui
- [ ] Verifică că vede executanții din regiunea contractelor (nu a lui)
- [ ] Verifică că poate asigna task-uri la executanții din regiunea contractului
- [ ] Verifică dashboard-ul managerului

### **3. Testare Funcționalități Existente**
- [ ] Admin - verifică că vede tot
- [ ] Executant - verifică că vede task-urile asignate
- [ ] Auditor - verifică că funcționează read-only
- [ ] Notificări - verifică că funcționează corect
- [ ] Rapoarte - verifică că funcționează pentru directori

### **4. Testare Edge Cases**
- [ ] Manager fără contracte asignate
- [ ] Director cu region_id NULL (nu ar trebui să existe)
- [ ] Contract fără manager_id
- [ ] Migrare director existent fără region_id

---

## Ordine Recomandată de Implementare

1. **Faza 1: Director - Eliminare Super User** (1-1.5h)
   - ContractManagementService
   - UserManagementService
   - UserController (validare)

2. **Faza 2: Manager - Filtrare pe Contracte** (1.5-2h)
   - ContractManagementService (deja OK)
   - TaskManagementService
   - SubdivisionManagementService
   - DashboardService

3. **Faza 3: Manager - Executanți din Regiunea Contractului** (0.5-1h)
   - UserManagementService
   - DashboardService

4. **Faza 4: Testare & Fix** (1-2h)
   - Testare completă
   - Fix bugs
   - Verificare edge cases

---

## Concluzie

**Rata de Succes:** 85-90%

**Riscuri:**
- ⚠️ Locații multiple de modificat
- ⚠️ Dependențe între servicii
- ⚠️ Testare extensivă necesară

**Puncte Pozitive:**
- ✅ Schema deja pregătită
- ✅ Metode helper existente
- ✅ Structură clară de roluri

**Recomandare:** 
- Să fac modificările în faze (cum e planificat mai sus)
- Testare după fiecare fază
- Backup înainte de începere

