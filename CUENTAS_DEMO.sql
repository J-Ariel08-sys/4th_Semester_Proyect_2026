DO $$
BEGIN
    IF current_database() <> 'rotes_clinicos_db'
       OR NOT EXISTS (SELECT 1 FROM public.users WHERE email = 'admin@siclin.test' AND role = 'administrador') THEN
        RAISE EXCEPTION 'La base no coincide con SICLIN Hito 4 restaurado.';
    END IF;
END
$$;

WITH nomina(name,email,role,matricula,academic_average,merit) AS (
    VALUES
    ('Sofía Mamani Flores', 'estudiante01@siclin.test', 'interno', 'SIM-I-01', 74, 68),
    ('Mateo Apaza Vilca', 'estudiante02@siclin.test', 'interno', 'SIM-I-02', 81, 79),
    ('Valentina Mendoza Apaza', 'estudiante03@siclin.test', 'interno', 'SIM-I-03', 88, 90),
    ('Daniel Condori Ramos', 'estudiante04@siclin.test', 'interno', 'SIM-I-04', 95, 71),
    ('Gabriela Paredes Ticona', 'estudiante05@siclin.test', 'interno', 'SIM-I-05', 76, 82),
    ('Andrés Arias Mamani', 'estudiante06@siclin.test', 'interno', 'SIM-I-06', 83, 93),
    ('Natalia Quispe Navarro', 'estudiante07@siclin.test', 'interno', 'SIM-I-07', 90, 74),
    ('Emiliano Ticona Quispe', 'estudiante08@siclin.test', 'interno', 'SIM-I-08', 97, 85),
    ('Luciana Vilca Salazar', 'estudiante09@siclin.test', 'interno', 'SIM-I-09', 78, 96),
    ('Bruno Vargas Choque', 'estudiante10@siclin.test', 'interno', 'SIM-I-10', 85, 77),
    ('Camila Nina Arias', 'estudiante11@siclin.test', 'interno', 'SIM-I-11', 92, 88),
    ('Nicolás López Rojas', 'estudiante12@siclin.test', 'interno', 'SIM-I-12', 99, 69),
    ('Mariana Choque López', 'estudiante13@siclin.test', 'interno', 'SIM-I-13', 80, 80),
    ('Joaquín Navarro Paredes', 'estudiante14@siclin.test', 'interno', 'SIM-I-14', 87, 91),
    ('Carolina Ramos Cárdenas', 'estudiante15@siclin.test', 'interno', 'SIM-I-15', 94, 72),
    ('Adrián Flores Nina', 'estudiante16@siclin.test', 'interno', 'SIM-I-16', 75, 83),
    ('Elena Cruz Condori', 'estudiante17@siclin.test', 'interno', 'SIM-I-17', 82, 94),
    ('Sebastián Cárdenas Cruz', 'estudiante18@siclin.test', 'interno', 'SIM-I-18', 89, 75),
    ('Isabel Rojas Vargas', 'estudiante19@siclin.test', 'interno', 'SIM-I-19', 96, 86),
    ('Tomás Salazar Mendoza', 'estudiante20@siclin.test', 'interno', 'SIM-I-20', 77, 97),
    ('Sofía Vargas Rojas', 'estudiante21@siclin.test', 'interno', 'SIM-I-21', 84, 78),
    ('Mateo Nina López', 'estudiante22@siclin.test', 'interno', 'SIM-I-22', 91, 89),
    ('Valentina López Paredes', 'estudiante23@siclin.test', 'interno', 'SIM-I-23', 98, 70),
    ('Daniel Choque Cárdenas', 'estudiante24@siclin.test', 'interno', 'SIM-I-24', 79, 81),
    ('Gabriela Navarro Nina', 'estudiante25@siclin.test', 'interno', 'SIM-I-25', 86, 92),
    ('Andrés Ramos Condori', 'estudiante26@siclin.test', 'interno', 'SIM-I-26', 93, 73),
    ('Natalia Flores Cruz', 'estudiante27@siclin.test', 'interno', 'SIM-I-27', 74, 84),
    ('Emiliano Cruz Vargas', 'estudiante28@siclin.test', 'interno', 'SIM-I-28', 81, 95),
    ('Luciana Cárdenas Mendoza', 'estudiante29@siclin.test', 'interno', 'SIM-I-29', 88, 76),
    ('Bruno Rojas Flores', 'estudiante30@siclin.test', 'interno', 'SIM-I-30', 95, 87),
    ('Camila Salazar Vilca', 'estudiante31@siclin.test', 'interno', 'SIM-I-31', 76, 68),
    ('Nicolás Mamani Apaza', 'estudiante32@siclin.test', 'interno', 'SIM-I-32', 83, 79),
    ('Mariana Apaza Ramos', 'estudiante33@siclin.test', 'interno', 'SIM-I-33', 90, 90),
    ('Joaquín Mendoza Ticona', 'estudiante34@siclin.test', 'interno', 'SIM-I-34', 97, 71),
    ('Carolina Condori Mamani', 'estudiante35@siclin.test', 'interno', 'SIM-I-35', 78, 82),
    ('Adrián Paredes Navarro', 'estudiante36@siclin.test', 'interno', 'SIM-I-36', 85, 93),
    ('Elena Arias Quispe', 'estudiante37@siclin.test', 'interno', 'SIM-I-37', 92, 74),
    ('Sebastián Quispe Salazar', 'estudiante38@siclin.test', 'interno', 'SIM-I-38', 99, 85),
    ('Isabel Ticona Choque', 'estudiante39@siclin.test', 'interno', 'SIM-I-39', 80, 96),
    ('Tomás Vilca Arias', 'estudiante40@siclin.test', 'interno', 'SIM-I-40', 87, 77),
    ('Sofía Rojas Apaza', 'estudiante41@siclin.test', 'interno', 'SIM-I-41', 94, 88),
    ('Mateo Salazar Ramos', 'estudiante42@siclin.test', 'interno', 'SIM-I-42', 75, 69),
    ('Valentina Mamani Ticona', 'estudiante43@siclin.test', 'interno', 'SIM-I-43', 82, 80),
    ('Daniel Apaza Mamani', 'estudiante44@siclin.test', 'interno', 'SIM-I-44', 89, 91),
    ('Gabriela Mendoza Navarro', 'estudiante45@siclin.test', 'interno', 'SIM-I-45', 96, 72),
    ('Andrés Condori Quispe', 'estudiante46@siclin.test', 'interno', 'SIM-I-46', 77, 83),
    ('Natalia Paredes Salazar', 'estudiante47@siclin.test', 'interno', 'SIM-I-47', 84, 94),
    ('Emiliano Arias Choque', 'estudiante48@siclin.test', 'interno', 'SIM-I-48', 91, 75),
    ('Luciana Quispe Arias', 'estudiante49@siclin.test', 'interno', 'SIM-I-49', 98, 86),
    ('Bruno Ticona Rojas', 'estudiante50@siclin.test', 'interno', 'SIM-I-50', 79, 97),
    ('Camila Vilca López', 'estudiante51@siclin.test', 'interno', 'SIM-I-51', 86, 78),
    ('Nicolás Vargas Paredes', 'estudiante52@siclin.test', 'interno', 'SIM-I-52', 93, 89),
    ('Mariana Nina Cárdenas', 'estudiante53@siclin.test', 'interno', 'SIM-I-53', 74, 70),
    ('Joaquín López Nina', 'estudiante54@siclin.test', 'interno', 'SIM-I-54', 81, 81),
    ('Carolina Choque Condori', 'estudiante55@siclin.test', 'interno', 'SIM-I-55', 88, 92),
    ('Adrián Navarro Cruz', 'estudiante56@siclin.test', 'interno', 'SIM-I-56', 95, 73),
    ('Elena Ramos Vargas', 'estudiante57@siclin.test', 'interno', 'SIM-I-57', 76, 84),
    ('Sebastián Flores Mendoza', 'estudiante58@siclin.test', 'interno', 'SIM-I-58', 83, 95),
    ('Isabel Cruz Flores', 'estudiante59@siclin.test', 'interno', 'SIM-I-59', 90, 76),
    ('Tomás Cárdenas Vilca', 'estudiante60@siclin.test', 'interno', 'SIM-I-60', 97, 87),
    ('Sofía Ticona Paredes', 'estudiante61@siclin.test', 'interno', 'SIM-I-61', 78, 68),
    ('Mateo Vilca Cárdenas', 'estudiante62@siclin.test', 'interno', 'SIM-I-62', 85, 79),
    ('Valentina Vargas Nina', 'estudiante63@siclin.test', 'interno', 'SIM-I-63', 92, 90),
    ('Daniel Nina Condori', 'estudiante64@siclin.test', 'interno', 'SIM-I-64', 99, 71),
    ('Gabriela López Cruz', 'estudiante65@siclin.test', 'interno', 'SIM-I-65', 80, 82),
    ('Andrés Choque Vargas', 'estudiante66@siclin.test', 'interno', 'SIM-I-66', 87, 93),
    ('Natalia Navarro Mendoza', 'estudiante67@siclin.test', 'interno', 'SIM-I-67', 94, 74),
    ('Emiliano Ramos Flores', 'estudiante68@siclin.test', 'interno', 'SIM-I-68', 75, 85),
    ('Luciana Flores Vilca', 'estudiante69@siclin.test', 'interno', 'SIM-I-69', 82, 96),
    ('Bruno Choque Ticona', 'tutor.demo01@siclin.test', 'tutor', NULL, 0, 0),
    ('Camila Navarro Mamani', 'tutor.demo02@siclin.test', 'tutor', NULL, 0, 0),
    ('Nicolás Ramos Navarro', 'tutor.demo03@siclin.test', 'tutor', NULL, 0, 0),
    ('Mariana Flores Quispe', 'tutor.demo04@siclin.test', 'tutor', NULL, 0, 0),
    ('Joaquín Cruz Salazar', 'tutor.demo05@siclin.test', 'tutor', NULL, 0, 0),
    ('Carolina Cárdenas Choque', 'tutor.demo06@siclin.test', 'tutor', NULL, 0, 0),
    ('Adrián Rojas Arias', 'tutor.demo07@siclin.test', 'tutor', NULL, 0, 0),
    ('Elena Salazar Rojas', 'tutor.demo08@siclin.test', 'tutor', NULL, 0, 0),
    ('Sebastián Mamani López', 'tutor.demo09@siclin.test', 'tutor', NULL, 0, 0),
    ('Isabel Apaza Paredes', 'tutor.demo10@siclin.test', 'tutor', NULL, 0, 0),
    ('Tomás Mendoza Cárdenas', 'tutor.demo11@siclin.test', 'tutor', NULL, 0, 0),
    ('Sofía Condori Nina', 'tutor.demo12@siclin.test', 'tutor', NULL, 0, 0),
    ('Mateo Paredes Condori', 'tutor.demo13@siclin.test', 'tutor', NULL, 0, 0),
    ('Valentina Arias Cruz', 'tutor.demo14@siclin.test', 'tutor', NULL, 0, 0),
    ('Daniel Quispe Vargas', 'tutor.demo15@siclin.test', 'tutor', NULL, 0, 0),
    ('Gabriela Ticona Mendoza', 'tutor.demo16@siclin.test', 'tutor', NULL, 0, 0),
    ('Andrés Vilca Flores', 'tutor.demo17@siclin.test', 'tutor', NULL, 0, 0),
    ('Natalia Vargas Vilca', 'tutor.demo18@siclin.test', 'tutor', NULL, 0, 0),
    ('Emiliano Nina Apaza', 'tutor.demo19@siclin.test', 'tutor', NULL, 0, 0),
    ('Luciana López Ramos', 'tutor.demo20@siclin.test', 'tutor', NULL, 0, 0),
    ('Paola Mercado Rivas', 'coordinador.demo1@siclin.test', 'coordinador', NULL, 0, 0),
    ('Esteban Calle Rocha', 'coordinador.demo2@siclin.test', 'coordinador', NULL, 0, 0)
), nuevas AS (
    INSERT INTO public.users (
        name,email,role,matricula,academic_average,merit,password,active,eligible,
        activated_at,access_approved_at,email_verified_at,created_at,updated_at
    )
    SELECT name,email,role,matricula,academic_average,merit,'$2y$12$cAMKnTO68ZJilw0OBI9Kze8WBvl6dMpH22CYEcu9nl91RBu73Prxm',TRUE,
        role = 'interno',NOW(),NOW(),NOW(),NOW(),NOW()
    FROM nomina
    ON CONFLICT (email) DO NOTHING
    RETURNING id
)
SELECT count(*) AS cuentas_agregadas FROM nuevas;

SELECT role AS rol, count(*) AS cuentas_habilitadas
FROM public.users
WHERE deleted_at IS NULL AND active AND activated_at IS NOT NULL AND email LIKE '%@siclin.test'
    AND (role = 'administrador' OR
         (access_approved_at IS NOT NULL AND (role <> 'interno' OR eligible)))
GROUP BY role ORDER BY role;
