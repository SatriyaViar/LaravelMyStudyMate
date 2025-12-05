<?php

namespace Database\Seeders;

use App\Models\StudyCard;
use App\Models\User;
use Illuminate\Database\Seeder;

class StudyCardSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first(); // Get first user

        if (!$user) {
            $this->command->info('No users found. Please create a user first.');
            return;
        }

        $studyCards = [
            [
                'user_id' => $user->id,
                'title' => 'Introduction to Laravel',
                'description' => 'Learn the basics of Laravel framework',
                'material_type' => 'text',
                'material_content' => 'Laravel is a web application framework with expressive, elegant syntax. It provides tools for routing, authentication, sessions, caching, and more. Laravel is designed to make web development easier and faster. It follows the Model-View-Controller (MVC) architectural pattern. The framework includes features like Eloquent ORM for database operations, Blade templating engine for views, and Artisan command-line tool for various tasks. Laravel also provides robust security features including protection against SQL injection, cross-site scripting, and cross-site request forgery. The framework has a large and active community, extensive documentation, and numerous packages available through Composer.',
            ],
            [
                'user_id' => $user->id,
                'title' => 'PHP Basics',
                'description' => 'Fundamental concepts of PHP programming',
                'material_type' => 'text',
                'material_content' => 'PHP is a popular server-side scripting language. It is used for web development and can be embedded into HTML. PHP stands for PHP: Hypertext Preprocessor, which is a recursive acronym. PHP code is executed on the server and the result is sent to the client as plain HTML. PHP supports various databases including MySQL, PostgreSQL, Oracle, and more. The language includes features like variables, arrays, functions, classes, and objects. PHP 8 introduced new features like named arguments, union types, attributes, and the JIT compiler for improved performance. PHP is open-source and free to use, making it accessible for developers worldwide. Common PHP applications include content management systems like WordPress, e-commerce platforms like Magento, and frameworks like Laravel and Symfony.',
            ],
            [
                'user_id' => $user->id,
                'title' => 'Database Design',
                'description' => 'Learn about database normalization and design',
                'material_type' => 'text',
                'material_content' => 'Database design is the process of organizing data according to a database model. Good database design ensures data integrity and efficiency. The design process involves identifying entities, attributes, and relationships. Normalization is a key concept that helps eliminate data redundancy and improve data integrity. There are several normal forms: First Normal Form (1NF) requires atomic values, Second Normal Form (2NF) eliminates partial dependencies, Third Normal Form (3NF) eliminates transitive dependencies, and Boyce-Codd Normal Form (BCNF) is a stricter version of 3NF. Entity-Relationship diagrams (ERD) are commonly used to visualize database structure. Primary keys uniquely identify records, while foreign keys establish relationships between tables. Indexes improve query performance but can slow down insert and update operations. Proper database design considers factors like scalability, performance, security, and maintainability.',
            ],
            [
                'user_id' => $user->id,
                'title' => 'Object-Oriented Programming',
                'description' => 'Understanding OOP concepts and principles',
                'material_type' => 'text',
                'material_content' => 'Object-Oriented Programming (OOP) is a programming paradigm based on the concept of objects. Objects contain data in the form of properties and code in the form of methods. The four main principles of OOP are Encapsulation, Abstraction, Inheritance, and Polymorphism. Encapsulation means bundling data and methods that work on that data within a single unit or class. Abstraction involves hiding complex implementation details and showing only essential features. Inheritance allows a class to inherit properties and methods from another class, promoting code reuse. Polymorphism enables objects of different classes to be treated as objects of a common parent class. Classes serve as blueprints for creating objects. OOP promotes modularity, reusability, and maintainability in software development. Popular OOP languages include Java, C++, Python, PHP, and C#.',
            ],
            [
                'user_id' => $user->id,
                'title' => 'RESTful API Design',
                'description' => 'Best practices for designing REST APIs',
                'material_type' => 'text',
                'material_content' => 'REST (Representational State Transfer) is an architectural style for designing networked applications. RESTful APIs use HTTP methods to perform operations on resources. The main HTTP methods are GET (retrieve), POST (create), PUT (update), PATCH (partial update), and DELETE (remove). RESTful APIs should be stateless, meaning each request contains all information needed to process it. Resources are identified by URIs (Uniform Resource Identifiers). Proper status codes should be returned: 200 for success, 201 for created, 400 for bad request, 401 for unauthorized, 404 for not found, and 500 for server errors. API versioning helps maintain backward compatibility. Response formats are typically JSON or XML. Best practices include using nouns for resource names, implementing proper authentication and authorization, providing clear documentation, using HTTPS for security, and implementing rate limiting to prevent abuse.',
            ],
        ];

        foreach ($studyCards as $card) {
            StudyCard::create($card);
        }

        $this->command->info('Study cards seeded successfully!');
    }
}