S.P.O.R. (Sistem Planificare Organizare Resurse) - Document Contextual

Acest document definește scopul, arhitectura și regulile de business pentru aplicația S.P.O.R., un ERP destinat firmelor de construcții/patronat, dezvoltat în CodeIgniter 4.

1. Descriere Generală (Overview)

S.P.O.R. este un dashboard administrativ centralizat care permite monitorizarea regiunilor, contractelor și task-urilor operaționale. Scopul principal este oferirea unei imagini de ansamblu ("Big Picture") pentru Admini și un tool de execuție clar pentru Directori Regionali, Manageri de Contract și Executanți.

Principii UX:

Monolith: Nu este SPA. Randare pe server (PHP View Cells) + jQuery/Vanilla JS.

Clean & Professional: Design minimalist, culori monocrome pentru widget-uri de status, fără "zgomot" vizual.

Viteză: Interacțiuni rapide, dropdown-uri cu funcție de search (Tom Select).

2. Actori și Roluri (RBAC Ierarhic)

Sistemul folosește un control al accesului bazat pe Niveluri Numerice. Un utilizator cu nivel superior moștenește automat drepturile nivelurilor inferioare.

Rol

Nivel

Descriere & Acces

Admin

100

Acces total (CRUD Useri, Configurare Globală, Ștergere Date).

Director

80

Director Regional. Vede doar resursele (utilizatori, contracte, task-uri) asignate pe regiunea lui. Are acces la rapoarte financiare și poate realoca resurse în cadrul regiunii. Trebuie să aibă obligatoriu region_id setat.

Manager

50

Manager de Contract. Vede doar contractele care sunt asignate direct lui (prin contracts.manager_id). Gestionează task-uri pentru contractele asignate. Creează task-uri și aprobă documente. Executanții pe care îi vede în listele de asignare sunt din regiunea contractului pe care îl administrează, nu din regiunea lui personală.

Executant

20

Rezolvă task-uri, încarcă fișiere, schimbă status task. Nu vede rapoarte financiare.

Auditor

10

Read-only. Poate vedea log-uri și istoricul task-urilor.

Regulă Implementare: În filtrele de rute se verifică: if (User.level >= Route.required_level).

3. Entități Principale și Relații

A. Regiuni & Contracte

Compania este împărțită geografic în Regiuni (ex: Nord-Vest, Sud).

Fiecare Regiune are mai multe Contracte (ex: "Autostrada Lot 4").

Fiecare Contract are Subdiviziuni/Faze (ex: "Proiectare", "Execuție Piloni").

B. Task-uri (Core Feature)

Un task este unitatea de bază a muncii.

Atribute: Titlu, Descriere, Departament (Tehnic, Juridic, etc.), Prioritate (Low/Med/High), Deadline.

Legături: Un task aparține unei Subdiviziuni (Contract) și este asignat unui User (Assignee).

Flow:

Creat de Manager/Director.

Notificare trimisă la Assignee (Real-time + Email).

Assignee încarcă fișiere și schimbă status (In Progress -> Review).

Creatorul validează (Done).

C. Notificări (Hibrid)

Sistem: Pusher (WebSockets) pentru alerte instant în header.

Fallback: Dacă userul nu este conectat la socket (offline), sistemul trimite Email.

Tipuri: Global (pentru toți) sau Privat (User specific).

4. Stack Tehnologic & Reguli de Cod

Backend (CodeIgniter 4)

Pattern: MVC Strict.

Business Logic: Nu scrie logică complexă în Controller. Folosește App\Services (ex: TaskService, NotificationService).

Database: MySQL. Folosește Migrations pentru orice modificare de structură.

Securitate: Validare input pe fiecare request. Upload-uri verificate prin MIME-Type.

Frontend

Framework: Bootstrap 5.3.

Interactivitate:

Tom Select: Pentru toate dropdown-urile (<select>). Permite căutare rapidă.

Chart.js: Pentru grafice (Task-uri per regiune).

DataTables: Pentru tabelele cu mulți utilizatori/task-uri.

Stil: Culori definite în CSS Variables (--primary-dark, --text-secondary). Widget-urile de "Team Load" folosesc clase monocrome (bg-monochrome-high).

5. Structura Bazei de Date (Schema Simplificată)

users: id, email, password, role_level, region_id (NULL pentru Admin = super user; Director trebuie să aibă region_id obligatoriu)

regions: id, name, manager_id (manager_id = Director Regional asignat regiunii)

contracts: id, region_id, manager_id, code, title (manager_id = Manager de Contract asignat)

tasks: id, contract_id, assignee_id, title, status, deadline

user_departments: user_id, department_id (many-to-many; user fără asociere = vede toate departamentele)

task_departments: task_id, department_id (many-to-many)

departments: id, name, color_code

files: id, task_id, filename, path

notifications: id, user_id, message, is_read, created_at

Notă: Doar Admin poate avea region_id NULL (super user, acces complet la toate regiunile). Director trebuie să aibă obligatoriu region_id setat (director regional). Un user fără asocieri în user_departments = vede toate departamentele.

6. Prompt de Sistem pentru AI

Dacă un AI generează cod pentru acest proiect, trebuie să respecte următoarea directivă:

"Ești un Senior PHP Developer specializat în CodeIgniter 4. Generezi cod curat, modular, folosind Service Layer pattern. Nu folosi librării externe inutile. Toate interactiunile cu baza de date se vor face prin intermediul modelelor. În frontend, bazează-te pe Bootstrap 5 și structura HTML existentă plus design-ul definit in public/assets/css/styles.css. Când implementezi filtre de acces, folosește logica ierarhică numerică (ex: Admin=100)."