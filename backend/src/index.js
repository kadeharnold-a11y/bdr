import express from "express";
import cors from "cors";
import dotenv from "dotenv";

dotenv.config();

// Importing db/index.js has the side effect of opening the SQLite file and
// running schema.sql/seeds - do this before the routers that depend on it.
import "./db/index.js";

import authRouter from "./routes/auth.js";
import citizensRouter from "./routes/citizens.js";
import applicationsRouter from "./routes/applications.js";
import paymentsRouter from "./routes/payments.js";
import trackingRouter from "./routes/tracking.js";
import staffRouter from "./routes/staff.js";

const app = express();
app.use(cors());
app.use(express.json());

app.get("/api/health", (req, res) => res.json({ status: "ok" }));

// Route prefix is /api/... to match the frontend's expectation (see
// frontend/src/views/SignUp.vue's "POST /api/auth/register/send-otp" note) -
// the stack is Node/Express, not Laravel, but the URL scheme matches.
app.use("/api/auth", authRouter);
app.use("/api/citizens", citizensRouter);
app.use("/api/applications", applicationsRouter);
app.use("/api/payments", paymentsRouter);
app.use("/api/tracking", trackingRouter);
app.use("/api/staff", staffRouter);

app.use((req, res) => {
  res.status(404).json({ error: { code: "NOT_FOUND", message: "No such route" } });
});

// Generic error handler - keeps the response shape consistent with
// shared/api-contract.md's error format.
app.use((err, req, res, next) => {
  console.error(err);
  res.status(err.status || 500).json({
    error: {
      code: err.code || "INTERNAL_ERROR",
      message: err.message || "Something went wrong",
    },
  });
});

const port = process.env.PORT || 4000;
app.listen(port, () => console.log(`HBDRP backend listening on :${port}`));
