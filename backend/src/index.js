import express from "express";
import cors from "cors";
import dotenv from "dotenv";

import citizensRouter from "./routes/citizens.js";
import applicationsRouter from "./routes/applications.js";
import paymentsRouter from "./routes/payments.js";
import trackingRouter from "./routes/tracking.js";
import staffRouter from "./routes/staff.js";

dotenv.config();

const app = express();
app.use(cors());
app.use(express.json());

app.get("/v1/health", (req, res) => res.json({ status: "ok" }));

app.use("/v1/citizens", citizensRouter);
app.use("/v1/applications", applicationsRouter);
app.use("/v1/payments", paymentsRouter);
app.use("/v1/tracking", trackingRouter);
app.use("/v1/staff", staffRouter);

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
