# Analiză: Șef de Departament - Răspunsuri și Propuneri

## ✅ Răspunsuri Primite:

1. **Role Level:** 70 ✓
2. **Structură:** Trebuie să ne asigurăm că pe fiecare regiune un departament are un singur șef
3. **Vizibilitate task-uri:** Din regiunea sa + departamentul său ✓
4. **Permisiuni:** Poate crea task-uri pentru departamentul său, dar doar în regiunea aia ✓
5. **Executanți:** Din regiunea sa + departamentul său ✓
6. **Dashboard:** KPIs pentru departamentul său ✓
7. **Creare:** Doar Admin ✓
8. **Multiple Roluri:** Un user poate fi simultan Manager de Contract ȘI Șef de Departament ⚠️

---

## 🔍 Analiză și Propuneri:

### **1. Structură de Date (Punctul 2)**

**Problema:** Trebuie să ne asigurăm că pe fiecare regiune, un departament are un singur șef.

**Opțiune A: `department_id` în tabela `users`**
```sql
ALTER TABLE users ADD COLUMN department_id INT UNSIGNED NULL;
-- Constraint UNIQUE pe (region_id, department_id) pentru role='department_head'
ALTER TABLE users ADD UNIQUE KEY unique_dept_head (region_id, department_id) 
WHERE role = 'department_head';
```
**Probleme:**
- MySQL nu suportă partial unique indexes (WHERE clause)
- Dacă un user e Manager, cum setăm `department_id`? (ar trebui să fie NULL pentru manager)
- Dacă un user e Manager ȘI Șef de Departament, ce facem?

**Opțiune B: Tabel separat `department_heads`** ⭐ **RECOMANDAT**
```sql
CREATE TABLE department_heads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NOT NULL,
    region_id INT UNSIGNED NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_dept_head_region (department_id, region_id)
);
```
**Avantaje:**
- ✅ Constraint UNIQUE pe (department_id, region_id) - un singur șef per departament/regiune
- ✅ Permite un user să fie Manager (role='manager') ȘI Șef de Departament simultan
- ✅ Flexibil - un user poate fi șef pentru mai multe departamente (dacă e nevoie)
- ✅ Clean separation - nu poluăm tabela `users`

**Recomandare:** **Opțiune B - Tabel separat `department_heads`**

---

### **2. Multiple Roluri (Punctul 8)**

**Situația:** Un user poate fi simultan:
- Manager de Contract (role='manager', manager_id în contracts)
- Șef de Departament (în tabelul department_heads)

**Problema:** Cum determinăm permisiunile?

**Soluție Propusă:**
- **Rol Principal:** Determinat de `users.role` și `users.role_level`
- **Roluri Secundare:** Verificăm în tabele separate (contracts.manager_id, department_heads)
- **Permisiuni:** UNION de permisiuni - userul are permisiunile de Manager ȘI de Șef de Departament

**Exemplu:**
```php
// User este Manager (role='manager')
// ȘI este șef de departament (în department_heads)

// Permisiuni:
- Poate vedea contractele asignate lui (manager_id)
- Poate vedea task-urile din departamentul său (department_heads)
- Poate crea task-uri pentru contractele sale
- Poate crea task-uri pentru departamentul său (în regiunea sa)
```

**Întrebare:** Când un user este Manager ȘI Șef de Departament:
- Dashboard-ul său arată ambele (contracte + task-uri din departament)?
- Sau preferi un dashboard unificat care combină ambele?

---

### **3. Logica de Filtrare**

**Pentru Șef de Departament:**
```php
// Task-uri vizibile:
WHERE tasks.subdivision_id IN (
    SELECT sd.id FROM subdivisions sd
    JOIN contracts c ON c.id = sd.contract_id
    WHERE c.region_id = :user_region_id
)
AND tasks.id IN (
    SELECT task_id FROM task_departments
    WHERE department_id = :user_department_id
)
```

**Pentru Manager ȘI Șef de Departament:**
```php
// UNION de permisiuni:
// Task-uri din contractele sale (manager_id)
// SAU
// Task-uri din departamentul său (department_heads + task_departments)
```

---

## 📋 Plan Propus:

### **Faza 1: Schema de Date**
1. ✅ Creare migration pentru `department_heads`
2. ✅ Adăugare `department_head` în ENUM('role')
3. ✅ Actualizare `role_level` pentru noul rol

### **Faza 2: Services**
1. ✅ `DepartmentHeadService` - logică de filtrare task-uri
2. ✅ Actualizare `TaskManagementService` - suport pentru șef de departament
3. ✅ Actualizare `DashboardService` - KPIs pentru departament
4. ✅ Actualizare `UserManagementService` - creare/editare șef de departament

### **Faza 3: Controllers**
1. ✅ Actualizare `UserController` - validare și creare șef de departament
2. ✅ Actualizare `TaskController` - permisiuni pentru șef de departament
3. ✅ Actualizare `DashboardController` - dashboard pentru șef de departament

### **Faza 4: Views**
1. ✅ Dashboard pentru șef de departament
2. ✅ Actualizare formular creare/editare user (pentru department_id)

---

## ❓ Întrebări Finale:

### **1. Structură de Date**
**Recomandare:** Tabel separat `department_heads` cu UNIQUE constraint pe (department_id, region_id)
**Confirmi?**

### **2. Multiple Roluri - Dashboard**
Când un user este Manager ȘI Șef de Departament:
- **A)** Dashboard unificat care combină contractele sale + task-urile din departament?
- **B)** Dashboard cu tabs (Contracte / Departament)?
- **C)** Dashboard care arată doar ce e mai relevant (prioritizează unul)?

### **3. Multiple Roluri - Permisiuni**
Când un user este Manager ȘI Șef de Departament:
- Poate crea task-uri pentru contractele sale (Manager)?
- Poate crea task-uri pentru departamentul său (Șef de Departament)?
- Sau doar unul dintre ele?

### **4. Vizibilitate Executanți**
Când un user este Manager ȘI Șef de Departament:
- Vede executanții din contractele sale (Manager)?
- Vede executanții din departamentul său (Șef de Departament)?
- Sau UNION (toți executanții relevanți)?

### **5. Nume Rol**
- **A)** `department_head` (engleză)
- **B)** `sef_departament` (română)
- **C)** Alt nume?

---

## 🎯 Concluzie Propunere:

**Structură Recomandată:**
```sql
-- Tabel nou
CREATE TABLE department_heads (
    user_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED NOT NULL,
    region_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, department_id, region_id),
    UNIQUE KEY unique_dept_head_region (department_id, region_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (region_id) REFERENCES regions(id)
);
```

**Avantaje:**
- ✅ Constraint UNIQUE pe (department_id, region_id) - un singur șef per departament/regiune
- ✅ Permite multiple roluri (Manager + Șef de Departament)
- ✅ Flexibil - un user poate fi șef pentru mai multe departamente (dacă e nevoie)
- ✅ Clean separation

**Aștept confirmarea pentru:**
1. Structura de date (tabel separat)
2. Logica pentru multiple roluri (dashboard, permisiuni)
3. Numele rolului

