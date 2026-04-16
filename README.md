Bermacy E-Pharmacy Platform 

Bermacy is a client-side E-commerce pharmacy website built using HTML, CSS & Js It operates without a backend server, relying on localStorage and sessionStorage to manage data such as cart items, users, and transactions.

Core Features

👉Online Medicine Shop: Product listing, search, filtering, sorting, and cart system

👉User Authentication: Registration and login using email or mobile

👉Cart & Checkout: Persistent cart with real-time updates

👉Payment System: Supports MTN MoMo, Airtel Money, Cards, and PayPal with

👉validation

👉Health Blog: Articles with search and category filtering

👉Contact System: Customer support form and FAQ

How the System Works

👉User selects medicines → saved in localStorage

👉User proceeds to checkout → login/register required

👉Cart and user data passed via sessionStorage

👉User selects payment method → validation and confirmation

👉Transaction ID generated → simulated email confirmation

👉Cart cleared → user redirected to homepage

Data Management

👉localStorage: stores cart, users, prescriptions, messages

👉sessionStorage: handles temporary checkout data

👉operations happen in the browser

Key Pages

👉Home (index): product display and quick cart

👉Shop: advanced filtering and full catalog

👉Login: authentication system

👉Payment: multi-method payment flow

👉Blog: health education content

👉Prescriptions: upload and track status

👉Contact: support and inquiries

Strengths

👉Fully functional without backend

👉Simple deployment 

👉Complete E-commerce flow (cart → payment → confirmation)

Limitations

👉No real payment integration

👉Passwords stored insecurely (plaintext)

👉No backend (no real order tracking or admin panel)

👉Email and uploads are simulated



Conclusion

Bermacy is a complete front-end pharmacy system demonstrating how a full e-commerce workflow can run entirely in the browser. It is suitable for learning, prototyping, or small-scale deployment, with clear potential for future backend integration.



