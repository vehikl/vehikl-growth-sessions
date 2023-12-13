<?php

namespace Database\Seeders;

use App\GrowthSession;
use App\User;
use App\UserType;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;


class BusyWeekSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $dayOfWeek = today()->dayOfWeek;
        $monday = match (TRUE) {
            $dayOfWeek === CarbonInterface::MONDAY => CarbonImmutable::today(),
            $dayOfWeek < CarbonInterface::MONDAY => CarbonImmutable::parse('Next Monday'),
            $dayOfWeek > CarbonInterface::MONDAY => CarbonImmutable::parse('Last Monday'),
        };

        $elements = [
            0 => ['topic' => 'Introduction to React', 'description' => 'Learn the fundamentals of React, including JSX, props, state, and events.'],
            1 => ['topic' => 'React Hooks', 'description' => 'Learn how to use React Hooks to build functional components.'],
            2 => ['topic' => 'React Context', 'description' => 'Learn how to use React Context to manage state in React applications.'],
            3 => ['topic' => 'React Router', 'description' => 'Learn how to use React Router to implement routing in React applications.'],
            4 => ['topic' => 'React Testing', 'description' => 'Learn how to test React components using Jest and React Testing Library.'],
            5 => ['topic' => 'React Best Practices', 'description' => 'Learn best practices for building scalable React applications.'],
            6 => ['topic' => 'Introduction to Java', 'description' => 'Learn the fundamentals of Java programming language.'],
            7 => ['topic' => 'Object-Oriented Programming in Java', 'description' => 'Learn the principles of object-oriented programming in Java.'],
            8 => ['topic' => 'Java Generics', 'description' => 'Learn how to use generics to build type-safe applications in Java.'],
            9 => ['topic' => 'Java Concurrency', 'description' => 'Learn how to build concurrent applications in Java using threads and locks.'],
            10 => ['topic' => 'Java Streams', 'description' => 'Learn how to use Java Streams API to process collections of data.'],
            11 => ['topic' => 'Java Best Practices', 'description' => 'Learn best practices for writing clean and maintainable code in Java.'],
            12 => ['topic' => 'Introduction to Python', 'description' => 'Learn the fundamentals of Python programming language.'],
            13 => ['topic' => 'Object-Oriented Programming in Python', 'description' => 'Learn the principles of object-oriented programming in Python.'],
            14 => ['topic' => 'Python Generators', 'description' => 'Learn how to use generators to build memory-efficient applications in Python.'],
            15 => ['topic' => 'Python Concurrency', 'description' => 'Learn how to build concurrent applications in Python using threads and locks.'],
            16 => ['topic' => 'Python Decorators', 'description' => 'Learn how to use decorators to add functionality to existing functions in Python.'],
            17 => ['topic' => 'Python Best Practices', 'description' => 'Learn best practices for writing clean and maintainable code in Python.'],
            18 => ['topic' => 'Introduction to Angular', 'description' => 'Learn the fundamentals of Angular, including components, services, and routing.'],
            19 => ['topic' => 'Angular Forms', 'description' => 'Learn how to build forms in Angular using template-driven and reactive forms.'],
            20 => ['topic' => 'Angular Routing', 'description' => 'Learn how to implement routing in Angular applications.'],
            21 => ['topic' => 'Angular Testing', 'description' => 'Learn how to test Angular components using Jasmine and Karma.'],
            22 => ['topic' => 'Angular Best Practices', 'description' => 'Learn best practices for building scalable Angular applications.'],
            23 => ['topic' => 'Introduction to Vue.js', 'description' => 'Learn the fundamentals of Vue.js, including components, directives, and routing.'],
            24 => ['topic' => 'Vue.js Forms', 'description' => 'Learn how to build forms in Vue.js using template-driven and reactive forms.'],
//            25 => ['topic' => 'Vue.js Routing', 'description' => 'Learn how to implement routing in Vue.js applications.'],
//            26 => ['topic' => 'Vue.js Testing', 'description' => 'Learn how to test Vue.js components using Jest.'],
//            27 => ['topic' => 'Vue.js Best Practices', 'description' => 'Learn best practices for building scalable Vue.js applications.'],
//            28 => ['topic' => 'Introduction to Node.js', 'description' => 'Learn the fundamentals of Node.js, including asynchronous programming and event-driven architecture.'],
//            29 => ['topic' => 'Node.js Modules', 'description' => 'Learn how to build and publish Node.js modules.'],
//            30 => ['topic' => 'Node.js Streams', 'description' => 'Learn how to use Node.js Streams API to process data.'],
//            31 => ['topic' => 'Node.js Testing', 'description' => 'Learn how to test Node.js applications using Jest.'],
//            32 => ['topic' => 'Node.js Best Practices', 'description' => 'Learn best practices for building scalable Node.js applications.'],
//            33 => ['topic' => 'Introduction to Express', 'description' => 'Learn the fundamentals of Express, including routing, middleware, and templating.'],
//            34 => ['topic' => 'Express Testing', 'description' => 'Learn how to test Express applications using Jest and Supertest.'],
//            35 => ['topic' => 'Express Best Practices', 'description' => 'Learn best practices for building scalable Express applications.'],
//            36 => ['topic' => 'Introduction to MongoDB', 'description' => 'Learn the fundamentals of MongoDB, including CRUD operations and aggregation pipeline.'],
//            37 => ['topic' => 'MongoDB Indexes', 'description' => 'Learn how to use indexes to improve the performance of MongoDB queries.'],
//            38 => ['topic' => 'MongoDB Aggregation Pipeline', 'description' => 'Learn how to use MongoDB Aggregation Pipeline to process data.'],
//            39 => ['topic' => 'MongoDB Best Practices', 'description' => 'Learn best practices for building scalable MongoDB applications.'],
//            40 => ['topic' => 'Introduction to SQL', 'description' => 'Learn the fundamentals of SQL, including CRUD operations and joins.'],
//            41 => ['topic' => 'SQL Indexes', 'description' => 'Learn how to use indexes to improve the performance of SQL queries.'],
//            42 => ['topic' => 'SQL Joins', 'description' => 'Learn how to use joins to query data from multiple tables in SQL.'],
//            43 => ['topic' => 'SQL Best Practices', 'description' => 'Learn best practices for building scalable SQL applications.'],
//            44 => ['topic' => 'Introduction to PostgreSQL', 'description' => 'Learn the fundamentals of PostgreSQL, including CRUD operations and joins.'],
//            45 => ['topic' => 'PostgreSQL Indexes', 'description' => 'Learn how to use indexes to improve the performance of PostgreSQL queries.'],
            ];

        foreach($elements as $element)
        {
            $maxAttendeeLimit = rand(4, 5);
            $growthSession = GrowthSession::factory()
            ->hasAttached(User::factory(rand(1, $maxAttendeeLimit))->vehiklMember(false), [], 'attendees')
            ->create([
                'date' => $monday->subWeek()->addDays(rand(0, 4)),
                'is_public' => fake()->boolean,
                'start_time' => Carbon::createFromTime(random_int(15, 16)),
                'attendee_limit' => $maxAttendeeLimit,
                'allow_watchers' => TRUE,
                'title' => $element['topic'],
                'topic' => $element['description'],
            ]);

            $owner = User::factory()->vehiklMember()->create();
            $owner->growthSessions()->attach($growthSession, ['user_type_id' => UserType::OWNER_ID]);
        }
    }
}
