# Ghid pentru Testarea Migrațiilor S.P.O.R.

## Pași de Testare

### 1. Verifică Statusul Migrațiilor

```bash
php spark migrate:status
```

Acest comand afișează toate migrațiile și dacă au fost rulate sau nu.
- `---` în coloanele "Migrated On" și "Batch" = migrația NU a fost rulată încă
- Data + Batch number = migrația a fost rulată

### 2. Rulează Toate Migrațiile

```bash
php spark migrate
```

Acest comand va rula TOATE migrațiile care nu au fost încă rulate, în ordinea timestamp-ului.

### 3. Rulează o Singură Migrație Specifică

```bash
php spark migrate -g default App 2025-01-01-100000
```

Unde `2025-01-01-100000` este versiunea migrației pe care vrei să o rulezi.

### 4. Rollback - Anulează Ultimul Batch de Migrații

```bash
php spark migrate:rollback
```

Acest comand va anula (down) toate migrațiile din ultimul batch.

### 5. Rollback cu Număr Specific de Batches

```bash
php spark migrate:rollback -b 2
```

Anulează ultimele 2 batches.

### 6. Verifică Baza de Date După Migrații

După ce rulezi migrațiile, verifică dacă tabelele au fost create:

```bash
# Conectează-te la MySQL
mysql -u root -p spor

# Apoi în MySQL:
SHOW TABLES;
DESCRIBE users;
DESCRIBE regions;
# etc.
```

## Configurare Baza de Date

Asigură-te că ai configurat corect baza de date în `.env` sau `app/Config/Database.php`:

```env
database.default.hostname = 127.0.0.1
database.default.database = spor
database.default.username = root
database.default.password = 
database.default.DBDriver = MySQLi
database.default.port = 3306
```

## Creare Baza de Date (dacă nu există)

Dacă baza de date `spor` nu există, creeaz-o:

```bash
mysql -u root -p
```

Apoi în MySQL:
```sql
CREATE DATABASE spor CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
EXIT;
```

## Ordinea Migrațiilor

Migrațiile vor fi rulate în următoarea ordine:

1. CreateRegions
2. CreateDepartments  
3. CreateUsers
4. AddUsersSelfReferencingFK
5. AddRegionsManagerForeignKey
6. CreateContracts
7. CreateSubdivisions
8. CreateUserDepartments
9. CreateTasks
10. CreateTaskAssignees
11. CreateTaskDepartments
12. CreateTaskComments
13. CreateTaskFiles
14. CreateTaskActivityLogs
15. CreateUserLogins
16. CreateNotifications
17. AddPerformanceIndexes

## Debugging

Dacă apare o eroare:

1. Verifică eroarea specifică în output
2. Verifică dacă baza de date există și conexiunea funcționează:
   ```bash
   php spark db:table users
   ```
3. Verifică log-urile în `writable/logs/`
4. Pentru rollback dacă ceva nu merge:
   ```bash
   php spark migrate:rollback
   ```

## Verificare Finală

După migrații, verifică că toate tabelele există:

```bash
mysql -u root -p spor -e "SHOW TABLES;"
```

Ar trebui să vezi:
- migrations
- regions
- departments
- users
- contracts
- subdivisions
- user_departments
- tasks
- task_assignees
- task_departments
- task_comments
- task_files
- task_activity_logs
- user_logins
- notifications

