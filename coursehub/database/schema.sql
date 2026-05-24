PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS staff (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    role TEXT NOT NULL,
    department TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    password_plain TEXT DEFAULT NULL,
    bio TEXT,
    photo_url TEXT,
    phone TEXT,
    office TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS programmes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    level TEXT NOT NULL CHECK(level IN ('Undergraduate','Postgraduate')),
    description TEXT,
    duration_years INTEGER DEFAULT 3,
    image_url TEXT,
    published INTEGER DEFAULT 0,
    programme_leader_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (programme_leader_id) REFERENCES staff(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS modules (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    code TEXT UNIQUE NOT NULL,
    description TEXT,
    credits INTEGER DEFAULT 20,
    year_of_study INTEGER NOT NULL CHECK(year_of_study BETWEEN 1 AND 5),
    image_url TEXT,
    module_leader_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_leader_id) REFERENCES staff(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS programme_modules (
    programme_id INTEGER NOT NULL,
    module_id INTEGER NOT NULL,
    PRIMARY KEY (programme_id, module_id),
    FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS students (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    phone TEXT,
    bio TEXT,
    reset_token TEXT,
    reset_expires DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS interest_registrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    first_name TEXT NOT NULL,
    last_name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT,
    programme_id INTEGER NOT NULL,
    student_id INTEGER,
    message TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS favourites (
    student_id INTEGER NOT NULL,
    programme_id INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (student_id, programme_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (programme_id) REFERENCES programmes(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS admins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contact_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    subject TEXT,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_programmes_published ON programmes(published);
CREATE INDEX IF NOT EXISTS idx_interest_programme ON interest_registrations(programme_id);
CREATE INDEX IF NOT EXISTS idx_interest_student ON interest_registrations(student_id);
CREATE INDEX IF NOT EXISTS idx_favourites_student ON favourites(student_id);

INSERT OR IGNORE INTO admins (username,email,password_hash) VALUES
('admin','admin@coursehub.ac.uk','$2y$10$EExPW4VMnC9ibdzdaV.hhu0MizE35P1r.Q5Z6.UgiI8WFK3M7SXLO');

INSERT OR IGNORE INTO staff (id,name,role,department,email,password_hash,password_plain,bio,phone,office) VALUES
(1,'Dr. Alice Johnson','Senior Lecturer','Computer Science','a.johnson@coursehub.ac.uk','$2y$10$wFbDZUE8EOKFfe0Zwsbqu.Oa2P843WtBZOpBjaX5kcc8aZ/ESinuK','Staff1234!','Dr. Alice Johnson is a Senior Lecturer in Computer Science with over 12 years of teaching experience. She completed her PhD at the University of Edinburgh, specialising in distributed systems and cloud computing. Her research has been published in numerous peer-reviewed journals, and she currently leads the Software Engineering module portfolio. Outside academia she mentors women entering technology careers.','+44 (0)1234 567 001','Room CS-214, Computing Building'),
(2,'Prof. Mark Stevens','Professor','Business Administration','m.stevens@coursehub.ac.uk','$2y$10$wFbDZUE8EOKFfe0Zwsbqu.Oa2P843WtBZOpBjaX5kcc8aZ/ESinuK','Staff1234!','Professor Mark Stevens holds a Chair in Strategic Management and is the author of three internationally acclaimed textbooks on organisational leadership. With 20 years of combined industry and academic experience, he brings real-world insight to the Business Administration programme.','+44 (0)1234 567 002','Room BA-301, Business School'),
(3,'Dr. Priya Patel','Lecturer','Data Science','p.patel@coursehub.ac.uk','$2y$10$wFbDZUE8EOKFfe0Zwsbqu.Oa2P843WtBZOpBjaX5kcc8aZ/ESinuK','Staff1234!','Dr. Priya Patel is a Lecturer in Data Science whose research sits at the intersection of machine learning and healthcare analytics. She received her doctorate from Imperial College London and spent three years as a data scientist at a leading pharmaceutical company before returning to academia.','+44 (0)1234 567 003','Room DS-107, Data Science Centre'),
(4,'Mr. James Carter','Teaching Assistant','Computer Science','j.carter@coursehub.ac.uk','$2y$10$wFbDZUE8EOKFfe0Zwsbqu.Oa2P843WtBZOpBjaX5kcc8aZ/ESinuK','Staff1234!','James Carter is a Teaching Assistant in Computer Science and a doctoral researcher investigating web performance optimisation techniques. He completed his MSc in Software Engineering with Distinction and supports delivery of Web Development and Database Systems modules.','+44 (0)1234 567 004','Room CS-110, Computing Building'),
(5,'Dr. Fatima Al-Hassan','Lecturer','Cybersecurity','f.alhassan@coursehub.ac.uk','$2y$10$wFbDZUE8EOKFfe0Zwsbqu.Oa2P843WtBZOpBjaX5kcc8aZ/ESinuK','Staff1234!','Dr. Fatima Al-Hassan is a Lecturer in Cybersecurity with a background in penetration testing and digital forensics. She previously worked as a security consultant for a global audit firm and holds multiple industry certifications including OSCP and CISSP.','+44 (0)1234 567 005','Room SEC-205, Engineering Building'),
(6,'Prof. David Okonkwo','Professor','Artificial Intelligence','d.okonkwo@coursehub.ac.uk','$2y$10$wFbDZUE8EOKFfe0Zwsbqu.Oa2P843WtBZOpBjaX5kcc8aZ/ESinuK','Staff1234!','Professor David Okonkwo is a leading authority on Artificial Intelligence and Natural Language Processing. He has published over 80 peer-reviewed papers, holds four patents, and has received funding from EPSRC, Innovate UK, and the European Research Council.','+44 (0)1234 567 006','Room AI-401, Research Tower');

INSERT OR IGNORE INTO programmes (id,title,slug,level,description,duration_years,published,programme_leader_id) VALUES
(1,'BSc Computer Science','bsc-computer-science','Undergraduate','Our flagship BSc Computer Science programme equips students with a rigorous grounding in the theory, design, and application of computing systems. Over three years, students progress from foundational programming and mathematics through to advanced topics such as distributed systems, artificial intelligence, and software architecture. The programme blends lectures, hands-on laboratory work, and a substantial final-year project.',3,1,1),
(2,'BSc Business Administration','bsc-business-administration','Undergraduate','The BSc Business Administration programme prepares students for leadership and management careers in a global economy. Students explore the full business lifecycle from entrepreneurship and marketing through to finance, operations, and strategic management. The curriculum is enriched by live case studies, guest lectures from industry leaders, and an optional year-long placement.',3,1,2),
(3,'BSc Cybersecurity','bsc-cybersecurity','Undergraduate','The BSc Cybersecurity programme responds to the explosive global demand for security professionals. Students gain both theoretical knowledge and practical skills in network defence, ethical hacking, digital forensics, and security governance. The programme includes a dedicated Security Operations Lab where students simulate real-world attack and defence scenarios.',3,1,5),
(4,'BSc Artificial Intelligence','bsc-artificial-intelligence','Undergraduate','The BSc Artificial Intelligence programme is designed for students who want to shape the future of intelligent systems. Covering machine learning, neural networks, computer vision, natural language processing, and ethical AI, the programme combines mathematical rigour with substantial practical project work.',3,1,6),
(5,'BSc Data Science','bsc-data-science','Undergraduate','The BSc Data Science programme trains students to collect, process, analyse, and visualise large-scale datasets in order to drive evidence-based decision-making. The curriculum covers statistical foundations, database engineering, machine learning, and data storytelling.',3,1,3),
(6,'BSc Software Engineering','bsc-software-engineering','Undergraduate','The BSc Software Engineering programme focuses on the disciplined, scalable development of high-quality software systems. Students learn software design patterns, agile methodologies, DevOps practices, testing strategies, and human-computer interaction.',3,1,1),
(7,'MSc Computer Science','msc-computer-science','Postgraduate','The MSc Computer Science is an intensive two-year programme suitable for graduates seeking to deepen their knowledge and advance into research or senior engineering roles. Year 1 covers advanced algorithms, cloud computing, and software architecture. Year 2 focuses on independent research culminating in a dissertation.',2,1,1),
(8,'MSc Data Science','msc-data-science','Postgraduate','The MSc Data Science is a rigorous two-year postgraduate programme training students to extract meaningful insight from complex, high-volume data. Year 1 covers advanced machine learning, big data infrastructure, and statistical methods. Year 2 is devoted to applied research and a substantial dissertation.',2,1,3),
(9,'MSc Cybersecurity','msc-cybersecurity','Postgraduate','The MSc Cybersecurity provides advanced two-year training for professionals seeking expertise in information security, digital forensics, and cyber risk management. The programme is certified by the National Cyber Security Centre (NCSC). Year 2 includes an industry-linked research dissertation.',2,1,5),
(10,'MSc Artificial Intelligence','msc-artificial-intelligence','Postgraduate','The MSc Artificial Intelligence is an advanced two-year programme covering the full breadth of modern AI from classical search through to deep reinforcement learning and transformer architectures. Year 2 involves original research in the AI Research Lab and a publishable dissertation.',2,1,6),
(11,'MSc Business Analytics','msc-business-analytics','Postgraduate','The MSc Business Analytics is a two-year programme bridging data science and strategic management, delivered in partnership with several FTSE 100 companies. Year 1 builds analytical and technical skills; Year 2 applies them to a live business research dissertation.',2,1,2),
(12,'MSc Software Engineering','msc-software-engineering','Postgraduate','The MSc Software Engineering is a two-year programme advancing students ability to design and lead delivery of complex software systems. Year 1 covers architecture, security engineering, and cloud-native development. Year 2 centres on an industry-standard group project and dissertation.',2,1,1);

INSERT OR IGNORE INTO modules (id,title,code,description,credits,year_of_study,module_leader_id) VALUES
(1,'Introduction to Programming','CS101','This module provides a comprehensive introduction to programming using Python. Students begin with fundamental concepts such as variables, data types, operators, and control flow before progressing to functions, recursion, and object-oriented design. Weekly laboratory sessions reinforce theoretical content through practical coding exercises, and students complete a summative project in which they design and implement a small application of their choice. No prior programming experience is assumed.',20,1,1),
(2,'Mathematics for Computing','CS102','A solid mathematical foundation is essential for advanced study in computer science and data science. This module covers propositional and predicate logic, proof techniques, set theory, relations, functions, combinatorics, graph theory, probability, and introductory linear algebra. Concepts are consistently illustrated with computational examples, and students complete problem sets that link mathematical ideas to algorithm design and data analysis.',20,1,1),
(3,'Introduction to Business','BBA101','This module introduces students to the fundamental structures, functions, and environments of modern organisations. Topics include the nature of business, organisational forms, the business environment using PESTLE analysis, stakeholder theory, marketing basics, human resource management, and introductory accounting. Students develop the analytical vocabulary needed for later study.',20,1,2),
(4,'Principles of Marketing','BBA102','Marketing Principles examines how organisations identify, anticipate, and satisfy customer needs profitably. The module covers the marketing mix, market research methods, consumer behaviour, segmentation, targeting and positioning, and digital marketing channels. Students apply theory to contemporary case studies and produce an individual marketing plan.',20,1,2),
(5,'Network Fundamentals','SEC101','This module establishes the networking knowledge that underpins both cybersecurity and general computing study. Students learn the OSI and TCP/IP reference models, IP addressing and subnetting, routing and switching, DNS, DHCP, and HTTP/S. Practical laboratory sessions use Cisco Packet Tracer and Wireshark to give hands-on experience of configuring and troubleshooting networks.',20,1,5),
(6,'Introduction to Artificial Intelligence','AI101','This module surveys the breadth of artificial intelligence from its historical roots to contemporary applications. Topics include intelligent agents, problem-solving by search, constraint satisfaction, knowledge representation, logical inference, probabilistic reasoning, and an introduction to machine learning. Practical sessions use Python and introductory ML libraries.',20,1,6),
(7,'Statistical Foundations','DS101','Statistical Foundations provides the quantitative grounding essential for data science. Students cover descriptive statistics, probability distributions, hypothesis testing, confidence intervals, correlation, and simple linear regression. All concepts are implemented in Python using NumPy, pandas, and SciPy, reinforcing learning through computation.',20,1,3),
(8,'Web Technologies','CS201','This module introduces the technologies that power the modern web including HTML5, CSS3, JavaScript for client-side interactivity, server-side development using PHP and the Slim framework, RESTful API design, and an introduction to relational databases. Students build and deploy a full-stack web application as their summative assessment.',20,2,4),
(9,'Data Structures and Algorithms','CS202','Data Structures and Algorithms is a cornerstone module covering the design, implementation, and analysis of fundamental data structures including arrays, linked lists, stacks, queues, trees, heaps, hash tables, and graphs, alongside classic algorithms for searching, sorting, and graph traversal. Time and space complexity analysis using Big-O notation is a central thread.',20,2,1),
(10,'Database Systems','CS203','Database Systems explores the design and implementation of relational database management systems. Topics include the relational model, entity-relationship modelling, normalisation through BCNF, SQL including DDL and DML, transactions, concurrency control, and an introduction to NoSQL databases. Students design a normalised schema and implement it using PostgreSQL.',20,2,4),
(11,'Business Strategy','BBA201','Business Strategy equips students with analytical frameworks used by managers and consultants. The module covers competitive analysis using Porters Five Forces, value chain analysis, corporate strategy, international strategy, strategic change management, and the balanced scorecard. Students apply frameworks in syndicate group exercises.',20,2,2),
(12,'Financial Management','BBA202','Financial Management introduces principles of corporate finance and financial decision-making. Topics include financial statement analysis, time value of money, capital budgeting using NPV and IRR, sources of finance, working capital management, and an introduction to derivatives. Students develop proficiency in financial modelling using Excel.',20,2,2),
(13,'Ethical Hacking and Penetration Testing','SEC201','This module provides a structured introduction to offensive security techniques within a legal and ethical framework. Students learn the penetration testing lifecycle covering reconnaissance, scanning, exploitation, post-exploitation, and reporting. Topics include vulnerability assessment, OWASP Top 10 web attacks, network attacks, and privilege escalation using Kali Linux and Metasploit.',20,2,5),
(14,'Machine Learning','AI201','Machine Learning provides a rigorous introduction to learning from data. Students study supervised learning covering regression, classification, decision trees, random forests, SVMs, and neural networks, alongside unsupervised learning including clustering and PCA. All algorithms are implemented from scratch in Python before students apply scikit-learn and TensorFlow to real datasets.',20,2,3),
(15,'Big Data Technologies','DS201','Big Data Technologies examines infrastructure and tools for processing datasets that exceed the capacity of a single machine. Students learn Hadoop, Apache Spark covering RDDs, DataFrames and MLlib, stream processing with Apache Kafka, and cloud-based big data services. Students complete a project ingesting, processing, and visualising a large public dataset.',20,2,3),
(16,'Software Architecture and Design','SE201','Software Architecture and Design addresses high-level decisions that determine the structure of large software systems. Students study architectural styles including microservices and event-driven architecture, design patterns, quality attributes, and architecture documentation using the C4 model and UML. Case studies from industry illustrate how architectural decisions shape long-term system evolution.',20,2,1),
(17,'Algorithms and Complexity','CS301','This advanced module explores theoretical foundations of efficient computation. Students study advanced algorithm design paradigms including dynamic programming and network flow, alongside complexity theory covering classes P, NP, and NP-complete. Students learn to prove NP-completeness via reduction and design approximation algorithms for intractable problems.',20,3,1),
(18,'Cloud Computing and DevOps','CS302','Cloud Computing and DevOps prepares students for modern software delivery. Topics include Infrastructure as Code with Terraform, containerisation using Docker and Kubernetes, CI/CD pipelines, monitoring with Prometheus and Grafana, and cloud-native architecture on AWS and Azure. Students migrate a monolithic application to a cloud-native architecture.',20,3,1),
(19,'Organisational Behaviour','BBA301','Organisational Behaviour examines psychological and sociological factors influencing individual and group behaviour in organisations. Topics include motivation theories, leadership styles, team dynamics, organisational culture, communication, power and politics, conflict resolution, and organisational change management.',20,3,2),
(20,'Digital Forensics','SEC301','Digital Forensics equips students to investigate cybersecurity incidents. The module covers forensic principles and legal admissibility, disk imaging, file system analysis, memory forensics, network forensics, mobile device investigation, and anti-forensic techniques using Autopsy, Volatility, and FTK in a dedicated forensics lab.',20,3,5),
(21,'Deep Learning','AI301','Deep Learning provides an advanced treatment of neural network architectures covering convolutional neural networks for image tasks, recurrent neural networks for sequences, transformer architectures including BERT and GPT, generative adversarial networks, and reinforcement learning. Students use PyTorch and complete a substantial independent deep learning project.',20,3,6),
(22,'Natural Language Processing','AI302','Natural Language Processing examines computational approaches to understanding and generating human language. Topics include text preprocessing, word embeddings, sequence-to-sequence models, named entity recognition, sentiment analysis, question answering, and large language models. Students use spaCy and Hugging Face Transformers.',20,3,6),
(23,'Data Visualisation and Communication','DS301','Effective communication of data insights is as important as the analysis itself. This module covers principles of visual perception, the grammar of graphics, interactive visualisation using Plotly and Dash, geospatial visualisation, dashboard design, and storytelling with data. Students produce a complete data story from raw data to a polished interactive report.',20,3,3),
-- ── PG Year 1 modules (taught, foundational) ─────────────────────────────────
(24,'Research Methods and Academic Writing','PG001','This compulsory Year 1 module prepares postgraduate students for independent academic research. It covers research design across quantitative, qualitative, and mixed methods, literature review techniques, research ethics, statistical analysis, academic writing conventions, and presentation skills. Students produce a full research proposal that feeds directly into their Year 2 dissertation.',20,1,1),
(25,'Advanced Machine Learning','PG002','Advanced Machine Learning is a Year 1 module exploring current research frontiers including Bayesian learning, probabilistic graphical models, kernel methods, ensemble methods, meta-learning, and federated learning. Students critically engage with recent research papers and implement advanced techniques in Python. Co-taught with the AI Research Lab.',20,1,3),
(26,'Cyber Risk and Governance','PG003','Cyber Risk and Governance is a Year 1 module examining the strategic, legal, and regulatory dimensions of information security. Topics include risk assessment frameworks such as ISO 27001 and NIST CSF, GDPR and data protection law, security governance structures, business continuity planning, and incident response management.',20,1,5),
(27,'Business Intelligence and Analytics','PG004','Business Intelligence and Analytics is a Year 1 module equipping students with advanced quantitative and technological skills for business decision-making. Topics include data warehousing, OLAP, predictive analytics, A/B testing, causal inference, and prescriptive analytics. Students use Power BI, SQL Server Analysis Services, and Python throughout.',20,1,2),
(28,'Distributed Systems','PG005','Distributed Systems is a Year 1 module examining the theory and practice of building reliable, scalable systems across multiple machines. Topics include the CAP theorem, consensus algorithms covering Paxos and Raft, distributed databases, eventual consistency, microservices communication patterns, and fault tolerance patterns.',20,1,1),
-- ── PG Year 2 modules (applied, research, dissertation) ──────────────────────
(29,'Dissertation','PG006','The Dissertation is the Year 2 capstone of every postgraduate programme. Students undertake a substantial independent research or development project under close academic supervision. The project must demonstrate mastery of the subject area, the ability to formulate and investigate a research question, and capacity to produce work of publishable quality. Students submit approximately 15000 words and present to an academic panel.',60,2,1),
(30,'Advanced Software Architecture','PG007','This Year 2 module deepens students knowledge of large-scale software system design. Topics include event-driven architecture, domain-driven design, CQRS and event sourcing, API gateway patterns, serverless computing, and architectural fitness functions. Students conduct an architectural review of a real open-source system and propose a documented redesign.',20,2,1),
(31,'Deep Learning and Neural Architectures','PG008','This Year 2 module provides an in-depth treatment of modern deep learning systems. Students study convolutional and recurrent architectures, transformer models including BERT and GPT variants, diffusion models, and reinforcement learning from human feedback. Assessment includes a research-level project using PyTorch submitted as a conference-style paper.',20,2,6),
(32,'Incident Response and Digital Investigation','PG009','This Year 2 module develops advanced skills in responding to and investigating cybersecurity incidents. Students cover incident response planning, forensic acquisition and chain of custody, malware analysis, threat intelligence, and post-incident reporting. Practical sessions use industry tools in a dedicated cyber range environment.',20,2,5),
(33,'Strategic Data-Driven Decision Making','PG010','This Year 2 module focuses on applying advanced analytics to organisational strategy. Topics include executive dashboard design, Monte Carlo simulation for business forecasting, network analysis, natural language processing for market intelligence, and communicating analytical findings to non-technical stakeholders. Students complete a live consulting challenge with an industry partner.',20,2,2),
(34,'Cloud-Native Engineering and DevSecOps','PG011','This Year 2 module addresses the full software delivery lifecycle in cloud environments. Students study infrastructure as code, container orchestration with Kubernetes, CI/CD pipeline security, zero-trust networking, chaos engineering, and SRE practices. Teams build and deploy a cloud-native application meeting enterprise reliability and security standards.',20,2,1);

INSERT OR IGNORE INTO programme_modules VALUES
(1,1),(1,2),(1,8),(1,9),(1,10),(1,17),(1,18),
(2,3),(2,4),(2,11),(2,12),(2,19),
(3,1),(3,2),(3,5),(3,8),(3,13),(3,20),
(4,1),(4,2),(4,6),(4,14),(4,21),(4,22),
(5,2),(5,7),(5,10),(5,14),(5,15),(5,23),
(6,1),(6,2),(6,8),(6,9),(6,16),(6,18),
-- MSc Computer Science (id=7): Y1: Research Methods, Data Structures, Distributed Systems, Arch | Y2: Dissertation, Advanced Architecture, Cloud DevSecOps
(7,9),(7,24),(7,28),(7,16),(7,29),(7,30),(7,34),
-- MSc Data Science (id=8): Y1: Research Methods, ML, Big Data, Statistics | Y2: Dissertation, Deep Learning, Strategic Analytics
(8,24),(8,25),(8,15),(8,23),(8,29),(8,31),(8,33),
-- MSc Cybersecurity (id=9): Y1: Research Methods, Pen Testing, Forensics, Cyber Risk | Y2: Dissertation, Incident Response
(9,24),(9,13),(9,20),(9,26),(9,29),(9,32),
-- MSc Artificial Intelligence (id=10): Y1: Research Methods, ML, Deep Learning, NLP | Y2: Dissertation, Advanced ML, Deep Architectures
(10,24),(10,14),(10,21),(10,22),(10,29),(10,25),(10,31),
-- MSc Business Analytics (id=11): Y1: Research Methods, Big Data, BI Analytics | Y2: Dissertation, Strategic Data Decision Making
(11,24),(11,15),(11,27),(11,29),(11,33),
-- MSc Software Engineering (id=12): Y1: Research Methods, Architecture, Distributed Systems | Y2: Dissertation, Advanced Architecture, Cloud DevSecOps
(12,24),(12,16),(12,28),(12,18),(12,29),(12,30),(12,34);