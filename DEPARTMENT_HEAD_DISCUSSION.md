# Discuție: Rol Nou - Șef de Departament

## 📋 Ce am înțeles din cerere:

### **Rol Nou: "Șef de Departament"**

**Caracteristici:**
- Similar cu Director Regional (dar pentru departamente)
- Departamentele rămân globale (cele mari create deja)
- **La nivel de regiune** - fiecare regiune poate avea un șef de departament pentru un anumit departament
- Vede doar task-urile asignate departamentului său

---

## ❓ Întrebări pentru Clarificare:

### **1. Poziționare în Ierarhie (Role Level)**

**Situația actuală:**
- Admin = 100
- Director = 80
- Manager = 50
- Executant = 20
- Auditor = 10

**Întrebare:** La ce nivel vrei să fie "Șef de Departament"?
- **Opțiune A:** Între Director și Manager (ex: 70 sau 65)
- **Opțiune B:** Între Manager și Executant (ex: 35 sau 40)
- **Opțiune C:** Alt nivel?

**Recomandare:** **70** (sub Director, dar peste Manager) - similar cu Director, dar cu domeniu mai specific.

---

### **2. Asignare și Structură**

**Întrebare:** Cum vrei să asignăm un șef de departament?
- **Opțiune A:** User are `department_id` + `region_id` (un singur departament per regiune)
- **Opțiune B:** Tabelă separată `department_heads` (user_id, department_id, region_id) - poate fi șef pentru mai multe departamente
- **Opțiune C:** Alt model?

**Recomandare:** **Opțiune A** - simplu, similar cu Director care are `region_id`.

---

### **3. Vizibilitate Task-uri**

**Situația:**
- Un task poate avea mai multe departamente (many-to-many prin `task_departments`)
- Un task aparține unei subdiviziuni → contract → regiune

**Întrebare:** Șeful de departament vede task-urile:
- **Opțiune A:** Doar din regiunea sa + cu departamentul său
- **Opțiune B:** Din toate regiunile, dar doar cu departamentul său
- **Opțiune C:** Alt criteriu?

**Recomandare:** **Opțiune A** - similar cu Director, vede doar din regiunea sa, dar filtrat pe departament.

---

### **4. Permisiuni pentru Creare/Editare Task-uri**

**Întrebare:** Șeful de departament poate:
- **A)** Doar să vadă task-uri (read-only pentru task-uri)?
- **B)** Să creeze task-uri pentru departamentul său?
- **C)** Să editeze task-uri din departamentul său?
- **D)** Să asigneze executanți la task-uri?

**Recomandare:** **B + C + D** - similar cu Manager, dar limitat la departamentul său.

---

### **5. Acces la Contracte și Subdiviziuni**

**Întrebare:** Șeful de departament vede:
- **A)** Doar task-urile (fără acces la contracte/subdiviziuni)?
- **B)** Contractele și subdiviziunile care au task-uri din departamentul său?
- **C)** Toate contractele din regiunea sa (dar doar task-urile din departamentul său)?

**Recomandare:** **B** - context limitat doar la ce are legătură cu departamentul său.

---

### **6. Vizibilitate Executanți**

**Întrebare:** Șeful de departament vede:
- **A)** Doar executanții din departamentul său din regiunea sa?
- **B)** Toți executanții din departamentul său (toate regiunile)?
- **C)** Doar executanții care au task-uri asignate în departamentul său?

**Recomandare:** **A** - executanții din departamentul său din regiunea sa.

---

### **7. Dashboard și Rapoarte**

**Întrebare:** Șeful de departament vede:
- **A)** Dashboard similar cu Manager (KPIs pentru departamentul său)?
- **B)** Dashboard similar cu Director (dar filtrat pe departament)?
- **C)** Un dashboard dedicat pentru departamente?

**Recomandare:** **A** - dashboard cu KPIs specifice departamentului său.

---

### **8. Creare Șef de Departament**

**Întrebare:** Cine poate crea un șef de departament?
- **A)** Doar Admin?
- **B)** Admin + Director (pentru regiunea sa)?
- **C)** Alt model?

**Recomandare:** **A** - similar cu Director, doar Admin.

---

### **9. Relația cu Manager de Contract**

**Întrebare:** Un user poate fi simultan:
- Manager de Contract pentru un contract
- Șef de Departament pentru un departament?
- Sau sunt roluri mutual exclusive?

**Recomandare:** **Mutual exclusive** - similar cu Director/Manager, un user are un singur rol.

---

## 🎯 Propunerea Mea (Bazată pe Similarități cu Director):

### **Structură:**
```
users:
  - role = 'department_head'
  - role_level = 70
  - region_id = obligatoriu (regiunea unde e șef)
  - department_id = obligatoriu (departamentul pentru care e șef) [NOU CÂMP]
```

### **Vizibilitate:**
- ✅ Vede task-uri: `region_id` din user = regiunea task-ului ȘI `department_id` din task-uri (prin `task_departments`)
- ✅ Vede executanții: din `region_id` + din `department_id` (prin `user_departments`)
- ✅ Dashboard: KPIs pentru departamentul său din regiunea sa

### **Permisiuni:**
- ✅ Poate crea task-uri pentru departamentul său
- ✅ Poate edita task-uri din departamentul său
- ✅ Poate asigna executanți din departamentul său
- ❌ Nu poate crea contracte/subdiviziuni (doar task-uri)
- ❌ Nu poate vedea rapoarte financiare (doar KPIs operaționale)

---

## ✅ Confirmă te rog:

1. **Role Level:** 70? (sau alt număr?)
2. **Structură:** `department_id` în tabela `users`? (sau tabel separat?)
3. **Vizibilitate task-uri:** Doar din regiunea sa + departamentul său? (sau toate regiunile?)
4. **Permisiuni:** Poate crea/edita task-uri pentru departamentul său?
5. **Executanți:** Vede doar din regiunea sa + departamentul său?
6. **Dashboard:** KPIs pentru departamentul său?
7. **Creare:** Doar Admin poate crea șef de departament?
8. **Mutual exclusive:** Un user poate fi șef de departament ȘI manager de contract simultan?

---

**După ce confirmi, pot crea un plan detaliat de implementare similar cu MODIFICATION_PLAN.md!** 🚀

