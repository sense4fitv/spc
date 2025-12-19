# Plan de Implementare - Șef de Departament (Department Head)

## 📋 Situația Confirmată

### **Rol Nou: Department Head**
- **Level:** 70 (între Director=80 și Manager=50)
- **Structură:** Tabel separat `department_heads` cu UNIQUE constraint pe (department_id, region_id)
- **Multiple Roluri:** Un user poate fi simultan Manager de Contract ȘI Șef de Departament (UNION de permisiuni)
- **Nume:** `department_head`

### **Permisiuni:**
- ✅ Vede task-uri: din regiunea sa + cu departamentul său
- ✅ Poate crea task-uri: pentru departamentul său, doar în regiunea sa
- ✅ Vede executanții: din regiunea sa + din departamentul său
- ✅ Dashboard: KPIs pentru departamentul său (unificat dacă e și Manager)

---

## 🗄️ Schema de Date

### **1. Migration - Tabel `department_heads`**
```sql
CREATE TABLE department_heads (
    user_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NOT NULL,
    region_id INT UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, department_id, region_id),
    UNIQUE KEY unique_dept_head_region (department_id, region_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE CASCADE
);
```

**Constraint UNIQUE:** Garantează că pe fiecare regiune, un departament are un singur șef.

---

### **2. Migration - Actualizare ENUM `role`**
```sql
ALTER TABLE users MODIFY role ENUM('admin', 'director', 'manager', 'executant', 'auditor', 'department_head') NOT NULL;
```

**Note:** Nu adăugăm `department_head` ca rol principal în `role`, ci verificăm dacă userul există în `department_heads`. Astfel, un user poate fi `role='manager'` dar și șef de departament.

---

## 📁 Fișiere de Modificat/Creat

### **1. Migrations (2 fișiere)**
- ✅ `app/Database/Migrations/YYYY-MM-DD-HHMMSS_CreateDepartmentHeads.php`
- ✅ `app/Database/Migrations/YYYY-MM-DD-HHMMSS_AddDepartmentHeadToRoleEnum.php` (opțional, dacă vrem să-l avem ca rol)

### **2. Models (2-3 fișiere)**
- ✅ `app/Models/DepartmentHeadModel.php` (NOU)
- ⚠️ `app/Models/UserModel.php` - metode helper pentru verificare șef de departament
- ⚠️ `app/Models/TaskModel.php` - metode pentru filtrare pe departament + regiune

### **3. Services (5-6 fișiere)**
- ✅ `app/Services/DepartmentHeadService.php` (NOU)
- ⚠️ `app/Services/TaskManagementService.php` - logica de filtrare pentru department_head
- ⚠️ `app/Services/DashboardService.php` - KPIs pentru departament
- ⚠️ `app/Services/UserManagementService.php` - creare/editare șef de departament
- ⚠️ `app/Services/ContractManagementService.php` - verificare dacă e relevant

### **4. Controllers (2-3 fișiere)**
- ⚠️ `app/Controllers/UserController.php` - CRUD pentru department heads
- ⚠️ `app/Controllers/TaskController.php` - permisiuni pentru department_head
- ⚠️ `app/Controllers/DashboardController.php` - dashboard pentru department_head

### **5. Views (2-3 fișiere)**
- ✅ `app/Views/dashboard/department_head.php` (NOU)
- ⚠️ `app/Views/users/create.php` - adăugare field pentru department_id (dacă e department_head)
- ⚠️ `app/Views/users/edit.php` - adăugare field pentru department_id

### **6. Config (1-2 fișiere)**
- ⚠️ `app/Config/Routes.php` - rute pentru department_head (dacă e nevoie)
- ⚠️ `app/Filters/AuthFilter.php` - verificare permisiuni pentru department_head

---

## 🔧 Detalii Modificări pe Fișier

### **Phase 1: Schema & Models**

#### **1.1. Migration - CreateDepartmentHeads**
- Creează tabelul `department_heads`
- UNIQUE constraint pe (department_id, region_id)
- Foreign keys

#### **1.2. DepartmentHeadModel**
- Metode: `getDepartmentHead($userId, $departmentId, $regionId)`
- Metode: `getDepartmentHeadsForRegion($regionId)`
- Metode: `getDepartmentHeadsForDepartment($departmentId)`
- Metode: `isDepartmentHead($userId, $departmentId, $regionId)`
- Metode: `getDepartmentsForUser($userId)`

#### **1.3. UserModel - Helper Methods**
- `isDepartmentHead($userId): bool`
- `getDepartmentHeadAssignments($userId): array`

---

### **Phase 2: Services**

#### **2.1. DepartmentHeadService (NOU)**
```php
class DepartmentHeadService {
    // Verifică dacă userul este șef de departament
    public function isDepartmentHead(int $userId): bool
    
    // Obține departamentele pentru care userul este șef
    public function getDepartmentsForUser(int $userId): array
    
    // Verifică dacă userul poate vedea un task (department + region)
    public function canViewTask(int $userId, int $taskId): bool
    
    // Obține task-urile vizibile pentru șef de departament
    public function getViewableTasks(int $userId): array
    
    // Verifică dacă poate crea task pentru un departament în regiune
    public function canCreateTaskForDepartment(int $userId, int $departmentId, int $regionId): bool
    
    // Obține executanții vizibili (din regiune + departament)
    public function getViewableExecutants(int $userId): array
}
```

#### **2.2. TaskManagementService**
**Modificări:**
- `getViewableTasks()` - adăugă suport pentru department_head
- `canViewTask()` - verifică dacă e department_head și taskul e din departamentul său + regiunea sa
- `canEditTask()` - similar
- `canCreateTask()` - verifică dacă poate crea pentru departamentul său în regiunea sa
- `getAllowedUsersForAssignment()` - include executanții din departamentul său

#### **2.3. DashboardService**
**Modificări:**
- `getKPIs()` - KPIs pentru departament (dacă e department_head)
- `getRegionsForDashboard()` - pentru department_head, arată doar regiunea sa
- `getContractsForDashboard()` - pentru department_head, arată contractele din regiunea sa
- `getTasksPerRegionChart()` - filtrare pe departament
- `getTeamWorkload()` - filtrare pe executanții din departament

#### **2.4. UserManagementService**
**Modificări:**
- `getViewableUsers()` - pentru department_head, vede executanții din departamentul său
- `canCreateUser()` - department_head nu poate crea utilizatori (doar view)
- `getAllowedUsersForAssignment()` - executanții din departamentul său

---

### **Phase 3: Controllers**

#### **3.1. UserController**
**Modificări:**
- `create()` - adăugare logică pentru department_head (selectare department_id + region_id)
- `store()` - validare și creare în `department_heads`
- `edit()` - afișare/editeare department_head assignments
- `update()` - actualizare department_head assignments
- Validare: dacă e department_head, trebuie să aibă department_id și region_id

#### **3.2. TaskController**
**Modificări:**
- `index()` - filtrare pentru department_head
- `view()` - verificare permisiuni pentru department_head
- `create()` - adăugare logică pentru department_head
- `store()` - validare și creare task pentru departament

#### **3.3. DashboardController**
**Modificări:**
- `index()` - adăugare view pentru department_head
- Verificare dacă userul este department_head (în `department_heads`)
- Creare view `dashboard/department_head.php`

---

### **Phase 4: Views**

#### **4.1. dashboard/department_head.php (NOU)**
- KPIs pentru departament
- Lista task-urilor din departamentul său
- Grafic task-uri per status
- Executanții din departament

#### **4.2. users/create.php & users/edit.php**
- Adăugare logică pentru department_head
- Selectare department_id și region_id
- Validare că pe regiune, departamentul nu are deja șef

---

## 🔐 Logică de Permisiuni

### **Verificare: Este userul șef de departament?**
```php
// În orice service/controller
$isDepartmentHead = $departmentHeadModel->isDepartmentHead($userId);
$departmentHeadAssignments = $departmentHeadModel->getDepartmentsForUser($userId);
```

### **Verificare: Poate vedea task-ul?**
```php
// Task trebuie să fie:
// 1. Din regiunea șefului de departament
// 2. Cu departamentul șefului de departament (prin task_departments)
```

### **Multiple Roluri (Manager + Department Head):**
```php
// Permisiuni = UNION
$canView = 
    ($isManager && contractIsAssigned($task, $userId)) ||
    ($isDepartmentHead && taskIsInDepartment($task, $departmentId) && taskIsInRegion($task, $regionId));
```

---

## 📊 Dashboard pentru Department Head

### **Conținut:**
- **KPIs:**
  - Task-uri active din departament
  - Task-uri întârziate din departament
  - Executanți din departament
  - Rata de completare (departament)

- **Task-uri:**
  - Listă task-uri din departamentul său (din regiunea sa)
  - Filtrare pe status, prioritate

- **Executanți:**
  - Listă executanți din departamentul său
  - Workload per executant

- **Grafice:**
  - Task-uri per status (pentru departament)
  - Evoluție task-uri în timp

---

## 🎯 Ordine Recomandată de Implementare

### **Faza 1: Schema & Models** (1h)
1. Migration pentru `department_heads`
2. `DepartmentHeadModel`
3. Metode helper în `UserModel`

### **Faza 2: Services - Department Head Logic** (2h)
1. `DepartmentHeadService`
2. Actualizare `TaskManagementService`
3. Actualizare `DashboardService`

### **Faza 3: User Management** (1h)
1. Actualizare `UserController` - CRUD pentru department heads
2. Actualizare views (create/edit user)

### **Faza 4: Task Management** (1h)
1. Actualizare `TaskController` - permisiuni pentru department_head
2. Verificare filtrare task-uri

### **Faza 5: Dashboard** (1.5h)
1. `dashboard/department_head.php`
2. Actualizare `DashboardController`

### **Faza 6: Multiple Roluri** (1h)
1. Logică pentru Manager + Department Head
2. Dashboard unificat
3. Testare UNION de permisiuni

### **Faza 7: Testare & Fix** (1-2h)
- Testare completă
- Fix bugs
- Edge cases

---

## ⚠️ Complexitate și Riscuri

### **Complexitate: MEDIE-ALTA** (7/10)

**Riscuri:**
1. ⚠️ **Multiple roluri** - logica de UNION poate fi complexă
2. ⚠️ **Filtrare task-uri** - trebuie să combinăm departament + regiune
3. ⚠️ **Dashboard unificat** - combinarea Manager + Department Head
4. ✅ **Schema pregătită** - structură clară de departamente există

### **Estimare:**
- **Fișiere noi:** 3-4
- **Fișiere modificate:** 12-15
- **Locații de modificat:** ~60-70
- **Timp estimat:** 8-10 ore de dezvoltare + 2-3 ore de testare
- **Rata de succes:** 85-90%

---

## ✅ Checkpoint-uri

### **După Faza 1:**
- [ ] Tabelul `department_heads` există
- [ ] Modelul funcționează
- [ ] Poți crea un department head prin SQL direct

### **După Faza 2:**
- [ ] Services returnează datele corecte
- [ ] Filtrarea task-urilor funcționează

### **După Faza 3:**
- [ ] Poți crea un department head prin UI
- [ ] Poți edita assignments

### **După Faza 4:**
- [ ] Department head vede doar task-urile din departamentul său
- [ ] Poate crea task-uri pentru departamentul său

### **După Faza 5:**
- [ ] Dashboard-ul se afișează corect
- [ ] KPIs sunt corecte

### **După Faza 6:**
- [ ] User Manager + Department Head vede ambele tipuri de task-uri
- [ ] Dashboard unificat funcționează

---

## 📝 Notițe Importante

1. **Constraint UNIQUE:** Garantează un singur șef per departament/regiune
2. **Multiple Roluri:** Verificăm simultan în `contracts.manager_id` ȘI `department_heads`
3. **Filtrare:** Task-uri trebuie să fie din regiunea șefului ȘI cu departamentul său
4. **Dashboard:** Unificat pentru Manager + Department Head (combină ambele vizualizări)

---

## 🚀 Ready to Implement!

**După aprobare, încep implementarea în ordinea recomandată!**

