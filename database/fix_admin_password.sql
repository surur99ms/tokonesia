USE tokonesia;
UPDATE users SET password='$2y$10$ZoavjL0lurSb0iI5rDPwUe/RvHQsSVAXc4Vlwncx/8BtZYb2b9712' WHERE email='admin@tokonesia.com';
SELECT id, name, email, role, LEFT(password,20) as pw_prefix FROM users WHERE email='admin@tokonesia.com';
