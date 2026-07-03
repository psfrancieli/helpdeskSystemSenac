"use client";

import React from "react";
import { motion } from "framer-motion";

export default function AnimatedButton() {
  return (
    <div className="relative group">
      {/* Button */}
      <motion.button
        whileHover={{ translateY: -1.3, boxShadow: "0px 8px 15px rgba(0, 0, 0, 0.3)"}}
        whileTap={{ scale: 0.97, translateY: 1, boxShadow: "0px 4px 6px rgba(0, 0, 0, 0.2)" }}
        className="relative flex items-center justify-center px-5 py-3 bg-amber-600 rounded-xl leading-none text-white font-semibold tracking-wider text-lg shadow-xl transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 focus:ring-offset-slate-900"
        style={{ cursor: "pointer" }}
      >
        <span className="relative">Concluir</span>
      </motion.button>
    </div>
  );
}
