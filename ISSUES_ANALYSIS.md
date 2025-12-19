# Analiză Probleme Identificate + Plan de Rezolvare

## 🔴 Probleme Identificate

### **1. Manager de Contract**

#### **1.1. Nu poate modifica contractul pe care îl are asignat**
**Locație:** `app/Services/ContractManagementService.php:150`
**Problemă:** Metoda `canEditContract()` returnează `false` pentru manager, indiferent dacă contractul este asignat sau nu.

**Rezolvare:** Trebuie să permitem managerului să editeze contractele asignate lui (prin `contract.manager_id`).

---

#### **1.2. Drill-down view - Redirectat la dashboard**
**Locație:** `app/Controllers/DashboardController.php:151`
**Problemă:** Verificarea permisiunilor pentru manager folosește `$userRegionId !== $contract['region_id']`, dar managerul ar trebui să verifice dacă contractul este asignat lui prin `manager_id`.

**Rezolvare:** Schimbă verificarea să folosească `contract.manager_id === $userId` pentru manager.

---

#### **1.3. Întrebare: Executanți cu region_id NULL?**
**Context:** Utilizatorul întreabă dacă executanții pot avea `region_id = NULL` (lucrează de acasă, pot face task-uri pentru toate regiunile).

**Opțiuni:**
- **A) DA** - Permitem executanți cu `region_id NULL`
  - ✅ Flexibilitate maximă
  - ⚠️ Logica de filtrare devine mai complexă (trebuie să verificăm dacă are `region_id` sau nu)
  - ⚠️ Pentru manageri: cum decid ce executanți să vadă?

- **B) NU** - Forțăm executanți să aibă `region_id`
  - ✅ Simplitate și claritate
  - ✅ Managerul știe exact ce executanți vede (din regiunea contractelor)
  - ❌ Mai puțin flexibil

**Recomandare:** **B) NU** - Menținem structura clară. Dacă un executant lucrează de acasă dar face task-uri pentru o regiune specifică, ar trebui să aibă acea regiune setată. Dacă chiar lucrează pentru toate regiunile, ar putea fi promovat la un rol superior (Manager/Director).

---

#### **1.4. Întrebare: Un manager poate avea mai multe contracte?**
**Context:** Din clarificările anterioare, managerul vede contractele asignate prin `contracts.manager_id`.

**Răspuns:** **DA** - Un manager poate avea mai multe contracte asignate. Structura DB permite asta (un manager_id poate apărea în mai multe rânduri din tabela `contracts`).

**Confirmare:** Trebuie să ne asigurăm că toată logica permite mai multe contracte per manager (deja pare să fie cazul).

---

### **2. Director Regional**

#### **2.1. Directorul nu poate adăuga contracte**
**Locație:** `app/Services/ContractManagementService.php:101-114`
**Problemă:** Metoda `canCreateContract()` permite directorului să creeze contracte doar în regiunea lui, dar probabil:
1. Nu are acces la formularul de creare (ContractController)
2. Sau există o problemă în validare

**Necesită verificare:** 
- Să verificăm dacă Directorul are acces la `/contracts/create`
- Să verificăm dacă validarea funcționează corect

**Rezolvare:** Dacă nu are acces, trebuie să permitem accesul la creare contracte pentru director.

---

### **3. Admin**

#### **3.1. Drill-down view - Toate contractele în loc de doar ale regiunii**
**Locație:** `app/Controllers/DashboardController.php:122` + `app/Services/DashboardService.php:288`
**Problemă:** Când adminul intră pe drill-down pentru o regiune (`/dashboard/region/{id}`), se folosește `getContractsForDashboard($userId, $role, $id)` unde `$id` este `region_id`. Dar metoda returnează toate contractele pentru admin.

**Rezolvare:** Trebuie să filtrăm contractele pe `region_id` pentru admin când accesează drill-down pentru o regiune specifică.

---

## 📋 Plan de Rezolvare

### **Faza 1: Manager de Contract**

1. ✅ **Permite manager să editeze contractele asignate**
   - Modifică `ContractManagementService::canEditContract()`
   - Verifică `contract.manager_id === $currentUserId`

2. ✅ **Fix drill-down view pentru manager**
   - Modifică `DashboardController::contractView()`
   - Verifică `contract.manager_id === $userId` în loc de `region_id`

3. ❓ **Clarificare: Executanți cu region_id NULL**
   - Așteptăm răspunsul utilizatorului

4. ✅ **Confirmare: Manager poate avea mai multe contracte**
   - Deja implementat corect în logică

---

### **Faza 2: Director Regional**

1. ✅ **Permite director să adauge contracte**
   - Verifică dacă are acces la `/contracts/create` (ContractController)
   - Verifică dacă validarea funcționează

---

### **Faza 3: Admin**

1. ✅ **Fix drill-down view pentru admin**
   - Modifică `DashboardController::regionView()` să filtreze contractele pe `region_id`
   - Sau modifică `getContractsForDashboard()` să accepte un parametru de filtrare pentru admin

---

## ❓ Întrebări pentru Clarificare

1. **Executanți cu region_id NULL?** - Recomandare: NU, menținem structura clară.

2. **Manager poate avea mai multe contracte?** - DA, deja implementat.

3. **Director poate adăuga contracte în regiunea lui?** - DA, trebuie să verificăm de ce nu funcționează.

---

## 🔧 Fișiere de Modificat

### **Manager:**
- `app/Services/ContractManagementService.php` - `canEditContract()`
- `app/Controllers/DashboardController.php` - `contractView()`

### **Director:**
- `app/Controllers/ContractController.php` - Verifică accesul la creare
- `app/Services/ContractManagementService.php` - Deja permite, dar verifică validarea

### **Admin:**
- `app/Controllers/DashboardController.php` - `regionView()` sau
- `app/Services/DashboardService.php` - `getContractsForDashboard()` - adaugă parametru de filtrare

---

## ✅ Concluzie

Majoritatea problemelor sunt clare și au soluții simple. Singura întrebare majoră este despre executanții cu `region_id NULL`, care necesită o decizie de design.

